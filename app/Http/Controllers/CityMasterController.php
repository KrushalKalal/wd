<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\State;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class CityMasterController extends Controller
{
    public function index(Request $request)
    {
        $query = City::with('state');

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhereHas('state', function ($sq) use ($request) {
                        $sq->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        // Filter by state
        if ($request->has('state_id') && $request->state_id) {
            $query->where('state_id', $request->state_id);
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $cities = $query->orderBy('name')->paginate($perPage);

        $states = State::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('CityMaster/Index', [
            'records' => $cities,
            'states' => $states,
            'filters' => [
                'search' => $request->search,
                'state_id' => $request->state_id,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create()
    {
        $states = State::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('CityMaster/Form', [
            'states' => $states,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'state_id' => 'required|exists:states,id',
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:cities,name,NULL,id,state_id,' . $request->state_id,
            ],
        ]);

        try {
            $data = $request->all();
            $data['is_active'] = true;
            City::create($data);

            return redirect()->route('city-master.index')
                ->with('success', 'City added successfully');
        } catch (\Throwable $e) {
            Log::error('City creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to add city')
                ->withInput();
        }
    }

    public function edit($id)
    {
        $city = City::findOrFail($id);
        $states = State::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('CityMaster/Form', [
            'city' => $city,
            'states' => $states,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'state_id' => 'required|exists:states,id',
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:cities,name,' . $id . ',id,state_id,' . $request->state_id,
            ],
        ]);

        try {
            $city = City::findOrFail($id);
            $city->update([
                'state_id' => $request->state_id,
                'name' => $request->name,
            ]);

            return redirect()->route('city-master.index')
                ->with('success', 'City updated successfully');
        } catch (\Throwable $e) {
            Log::error('City update failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update city')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $city = City::findOrFail($id);
            $city->delete();

            return redirect()->back()->with('success', 'City deleted successfully');
        } catch (\Throwable $e) {
            Log::error('City deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete city. It may have associated areas.');
        }
    }

    public function toggleStatus($id)
    {
        try {
            $city = City::findOrFail($id);
            $city->is_active = !$city->is_active;
            $city->save();

            return redirect()->back()->with('success', 'City status updated successfully');
        } catch (\Throwable $e) {
            Log::error('City toggle failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update city status');
        }
    }

    public function downloadTemplate()
    {
        try {
            $states = State::where('is_active', true)->orderBy('name')->pluck('name')->toArray();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Headers
            $sheet->setCellValue('A1', 'State Name');
            $sheet->setCellValue('B1', 'City Name');

            // Styling
            $sheet->getStyle('A1:B1')->getFont()->setBold(true);
            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension('B')->setAutoSize(true);

            // Create dropdown list for states in column A (rows 2-1000)
            $stateList = '"' . implode(',', $states) . '"';

            for ($row = 2; $row <= 1000; $row++) {
                $validation = $sheet->getCell('A' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setErrorTitle('Invalid State');
                $validation->setError('Please select a state from the dropdown');
                $validation->setPromptTitle('Select State');
                $validation->setPrompt('Choose a state from the list');
                $validation->setFormula1($stateList);
            }

            $writer = new Xlsx($spreadsheet);
            $filename = 'city_template_' . now()->format('Ymd_His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename={$filename}");
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;
        } catch (\Throwable $e) {
            Log::error('City template download failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to download template');
        }
    }

    public function uploadExcel(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(
                $request->file('excel_file')->getRealPath()
            );

            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

            $imported = 0;
            $errors = [];

            foreach (array_slice($rows, 1) as $index => $row) {
                $stateName = trim($row['A'] ?? '');
                $cityName = trim($row['B'] ?? '');

                if (!$stateName || !$cityName) {
                    $errors[] = "Row " . ($index + 2) . ": Both state and city names are required";
                    continue;
                }

                // Find state
                $state = State::whereRaw('LOWER(name) = ?', [strtolower($stateName)])->first();
                if (!$state) {
                    $errors[] = "Row " . ($index + 2) . ": State '{$stateName}' not found";
                    continue;
                }

                // Check if city already exists in this state
                $exists = City::where('state_id', $state->id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($cityName)])
                    ->first();

                if ($exists) {
                    $errors[] = "Row " . ($index + 2) . ": City '{$cityName}' already exists in {$stateName}";
                    continue;
                }

                City::create([
                    'state_id' => $state->id,
                    'name' => $cityName,
                    'is_active' => true,
                ]);

                $imported++;
            }

            $message = "{$imported} cities imported successfully";
            if (count($errors) > 0) {
                $message .= ". " . count($errors) . " rows skipped.";
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('City upload failed: ' . $e->getMessage());
            return back()->with('error', 'Excel upload failed: ' . $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\State;
use App\Models\City;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class AreaMasterController extends Controller
{
    public function index(Request $request)
    {
        $query = Area::with(['state', 'city']);

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhereHas('state', function ($sq) use ($request) {
                        $sq->where('name', 'like', '%' . $request->search . '%');
                    })
                    ->orWhereHas('city', function ($cq) use ($request) {
                        $cq->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        // Filter by state
        if ($request->has('state_id') && $request->state_id) {
            $query->where('state_id', $request->state_id);
        }

        // Filter by city
        if ($request->has('city_id') && $request->city_id) {
            $query->where('city_id', $request->city_id);
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $areas = $query->orderBy('name')->paginate($perPage);

        $states = State::where('is_active', true)->select('id', 'name')->orderBy('name')->get();


        return Inertia::render('AreaMaster/Index', [
            'records' => $areas,
            'states' => $states,
            'filters' => [
                'search' => $request->search,
                'state_id' => $request->state_id,
                'city_id' => $request->city_id,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create()
    {
        $states = State::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('AreaMaster/Form', [
            'states' => $states,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:areas,name,NULL,id,city_id,' . $request->city_id,
            ],
        ]);

        try {
            $data = $request->all();
            $data['is_active'] = true;
            Area::create($data);

            return redirect()->route('area-master.index')
                ->with('success', 'Area added successfully');
        } catch (\Throwable $e) {
            Log::error('Area creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to add area')
                ->withInput();
        }
    }

    public function edit($id)
    {
        $area = Area::findOrFail($id);
        $states = State::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('AreaMaster/Form', [
            'area' => $area,
            'states' => $states,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:areas,name,' . $id . ',id,city_id,' . $request->city_id,
            ],
        ]);

        try {
            $area = Area::findOrFail($id);
            $area->update([
                'state_id' => $request->state_id,
                'city_id' => $request->city_id,
                'name' => $request->name,
            ]);

            return redirect()->route('area-master.index')
                ->with('success', 'Area updated successfully');
        } catch (\Throwable $e) {
            Log::error('Area update failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update area')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $area = Area::findOrFail($id);
            $area->delete();

            return redirect()->back()->with('success', 'Area deleted successfully');
        } catch (\Throwable $e) {
            Log::error('Area deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete area');
        }
    }

    public function toggleStatus($id)
    {
        try {
            $area = Area::findOrFail($id);
            $area->is_active = !$area->is_active;
            $area->save();

            return redirect()->back()->with('success', 'Area status updated successfully');
        } catch (\Throwable $e) {
            Log::error('Area toggle failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update area status');
        }
    }

    public function downloadTemplate()
    {
        try {
            $states = State::where('is_active', true)->with('cities')->orderBy('name')->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Areas');

            // Headers
            $sheet->setCellValue('A1', 'State Name');
            $sheet->setCellValue('B1', 'City Name');
            $sheet->setCellValue('C1', 'Area Name');

            // Styling
            $headerStyle = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE0E0E0']
                ]
            ];
            $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);

            // Create hidden data sheet for cities organized by state
            $dataSheet = $spreadsheet->createSheet();
            $dataSheet->setTitle('Data');

            // State dropdown list
            $stateNames = $states->pluck('name')->toArray();
            $stateList = '"' . implode(',', $stateNames) . '"';

            // Write states and their cities to data sheet with named ranges
            $dataCol = 1;
            foreach ($states as $state) {
                $stateCities = $state->cities->pluck('name')->toArray();

                if (count($stateCities) > 0) {
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($dataCol);

                    // State name as header
                    $dataSheet->setCellValue($columnLetter . '1', $state->name);

                    // Cities below
                    $cityRow = 2;
                    foreach ($stateCities as $cityName) {
                        $dataSheet->setCellValue($columnLetter . $cityRow, $cityName);
                        $cityRow++;
                    }

                    // Create named range for this state (replace spaces and special chars with underscore)
                    $rangeName = preg_replace('/[^A-Za-z0-9]/', '_', $state->name);
                    $range = $columnLetter . '2:' . $columnLetter . ($cityRow - 1);

                    $spreadsheet->addNamedRange(
                        new \PhpOffice\PhpSpreadsheet\NamedRange(
                            $rangeName,
                            $dataSheet,
                            $range
                        )
                    );

                    $dataCol++;
                }
            }

            // Hide the data sheet
            $dataSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

            // Back to main sheet - Add State dropdown (Column A)
            for ($row = 2; $row <= 1000; $row++) {
                $validation = $sheet->getCell('A' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                $validation->setFormula1($stateList);
                $validation->setShowInputMessage(true);
                $validation->setPromptTitle('Select State');
                $validation->setPrompt('Choose a state first');
            }

            // Add City dropdown (Column B) - Dynamic based on state selection
            for ($row = 2; $row <= 1000; $row++) {
                $validation = $sheet->getCell('B' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);

                // INDIRECT formula to reference named range based on state selection
                // Replace spaces in state name with underscore to match named range
                $validation->setFormula1('INDIRECT(SUBSTITUTE(A' . $row . '," ","_"))');

                $validation->setShowInputMessage(true);
                $validation->setPromptTitle('Select City');
                $validation->setPrompt('Select state first, then city will load');
            }

            // Instructions Sheet
            $instructionSheet = $spreadsheet->createSheet();
            $instructionSheet->setTitle('Instructions');

            $instructionSheet->setCellValue('A1', '📋 HOW TO FILL THIS TEMPLATE:');
            $instructionSheet->setCellValue('A3', 'Step 1: Select State');
            $instructionSheet->setCellValue('A4', '   Click on Column A and select a state from the dropdown');
            $instructionSheet->setCellValue('A5', '');
            $instructionSheet->setCellValue('A6', 'Step 2: Select City');
            $instructionSheet->setCellValue('A7', '   Click on Column B - cities will automatically load for your selected state');
            $instructionSheet->setCellValue('A8', '   Select the city from the dropdown');
            $instructionSheet->setCellValue('A9', '');
            $instructionSheet->setCellValue('A10', 'Step 3: Enter Area Name');
            $instructionSheet->setCellValue('A11', '   Type the area name in Column C');
            $instructionSheet->setCellValue('A12', '');
            $instructionSheet->setCellValue('A13', 'Step 4: Save and Upload');
            $instructionSheet->setCellValue('A14', '   Save the file and upload it back to the system');
            $instructionSheet->setCellValue('A15', '');
            $instructionSheet->setCellValue('A16', '⚠️ IMPORTANT NOTES:');
            $instructionSheet->setCellValue('A17', '   • City dropdown is DYNAMIC - it changes based on your state selection');
            $instructionSheet->setCellValue('A18', '   • If you change the state, you must re-select the city');
            $instructionSheet->setCellValue('A19', '   • Check "City Reference" sheet to see all available cities');

            $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $instructionSheet->getStyle('A3')->getFont()->setBold(true)->setSize(13);
            $instructionSheet->getStyle('A6')->getFont()->setBold(true)->setSize(13);
            $instructionSheet->getStyle('A10')->getFont()->setBold(true)->setSize(13);
            $instructionSheet->getStyle('A13')->getFont()->setBold(true)->setSize(13);
            $instructionSheet->getStyle('A16')->getFont()->setBold(true)->setSize(13)->getColor()->setARGB('FFFF0000');

            $instructionSheet->getStyle('A4:A19')->getAlignment()->setWrapText(true);
            $instructionSheet->getColumnDimension('A')->setWidth(90);

            // City Reference Sheet
            $cityRefSheet = $spreadsheet->createSheet();
            $cityRefSheet->setTitle('City Reference');
            $cityRefSheet->setCellValue('A1', 'State Name');
            $cityRefSheet->setCellValue('B1', 'Available Cities');
            $cityRefSheet->getStyle('A1:B1')->applyFromArray($headerStyle);

            $row = 2;
            foreach ($states as $state) {
                $stateCities = $state->cities;
                if ($stateCities->count() > 0) {
                    foreach ($stateCities as $index => $city) {
                        if ($index === 0) {
                            $cityRefSheet->setCellValue('A' . $row, $state->name);
                            $cityRefSheet->getStyle('A' . $row)->getFont()->setBold(true);
                        } else {
                            $cityRefSheet->setCellValue('A' . $row, '');
                        }
                        $cityRefSheet->setCellValue('B' . $row, $city->name);
                        $row++;
                    }
                    // Separator row with light gray background
                    $cityRefSheet->getStyle('A' . $row . ':B' . $row)
                        ->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFE8E8E8');
                    $row++;
                }
            }

            $cityRefSheet->getColumnDimension('A')->setAutoSize(true);
            $cityRefSheet->getColumnDimension('B')->setAutoSize(true);

            // Set main sheet as active
            $spreadsheet->setActiveSheetIndex(0);
            $sheet->getColumnDimension('A')->setWidth(20);
            $sheet->getColumnDimension('B')->setWidth(20);
            $sheet->getColumnDimension('C')->setWidth(30);

            $writer = new Xlsx($spreadsheet);
            $filename = 'area_template_' . now()->format('Ymd_His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename={$filename}");
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;
        } catch (\Throwable $e) {
            Log::error('Area template download failed: ' . $e->getMessage());
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
                $areaName = trim($row['C'] ?? '');

                if (!$stateName || !$cityName || !$areaName) {
                    $errors[] = "Row " . ($index + 2) . ": State, city, and area names are required";
                    continue;
                }

                // Find state
                $state = State::whereRaw('LOWER(name) = ?', [strtolower($stateName)])->first();
                if (!$state) {
                    $errors[] = "Row " . ($index + 2) . ": State '{$stateName}' not found";
                    continue;
                }

                // Find city in this state
                $city = City::where('state_id', $state->id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($cityName)])
                    ->first();

                if (!$city) {
                    $errors[] = "Row " . ($index + 2) . ": City '{$cityName}' not found in {$stateName}";
                    continue;
                }

                // Check if area already exists in this city
                $exists = Area::where('city_id', $city->id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($areaName)])
                    ->first();

                if ($exists) {
                    $errors[] = "Row " . ($index + 2) . ": Area '{$areaName}' already exists in {$cityName}";
                    continue;
                }

                Area::create([
                    'state_id' => $state->id,
                    'city_id' => $city->id,
                    'name' => $areaName,
                    'is_active' => true,
                ]);

                $imported++;
            }

            $message = "{$imported} areas imported successfully";
            if (count($errors) > 0) {
                $message .= ". " . count($errors) . " rows skipped.";
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Area upload failed: ' . $e->getMessage());
            return back()->with('error', 'Excel upload failed: ' . $e->getMessage());
        }
    }

    // API endpoint to get cities by state
    public function getCitiesByState($stateId)
    {
        $cities = City::where('state_id', $stateId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($cities);
    }
}
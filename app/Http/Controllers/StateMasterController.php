<?php

namespace App\Http\Controllers;

use App\Models\State;
use App\Models\Zone;
use App\Helpers\RoleAccessHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class StateMasterController extends Controller
{
    public function index(Request $request)
    {
        $query = State::with('zone');

        // Apply role-based filter using helper
        $query = RoleAccessHelper::applyRoleFilter($query);

        // Search
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Zone filter
        if ($request->has('zone_id') && $request->zone_id) {
            $query->where('zone_id', $request->zone_id);
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $states = $query->orderBy('name')->paginate($perPage);

        // Get accessible zones for filter
        $zoneIds = RoleAccessHelper::getAccessibleZoneIds();
        $zones = Zone::whereIn('id', $zoneIds)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('StateMaster/Index', [
            'records' => $states,
            'zones' => $zones,
            'filters' => [
                'search' => $request->search,
                'zone_id' => $request->zone_id,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create()
    {
        $zoneIds = RoleAccessHelper::getAccessibleZoneIds();
        $zones = Zone::whereIn('id', $zoneIds)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('StateMaster/Form', [
            'zones' => $zones,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'zone_id' => 'required|exists:zones,id',
            'name' => 'required|string|max:255|unique:states,name,NULL,id,zone_id,' . $request->zone_id,
        ]);

        try {
            State::create([
                'zone_id' => $request->zone_id,
                'name' => $request->name,
                'is_active' => true,
            ]);

            return redirect()->route('state-master.index')
                ->with('success', 'State added successfully');
        } catch (\Throwable $e) {
            Log::error('State creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to add state')
                ->withInput();
        }
    }

    public function edit($id)
    {
        $state = State::findOrFail($id);

        $zoneIds = RoleAccessHelper::getAccessibleZoneIds();
        $zones = Zone::whereIn('id', $zoneIds)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('StateMaster/Form', [
            'state' => $state,
            'zones' => $zones,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'zone_id' => 'required|exists:zones,id',
            'name' => 'required|string|max:255|unique:states,name,' . $id . ',id,zone_id,' . $request->zone_id,
        ]);

        try {
            $state = State::findOrFail($id);
            $state->update([
                'zone_id' => $request->zone_id,
                'name' => $request->name,
            ]);

            return redirect()->route('state-master.index')
                ->with('success', 'State updated successfully');
        } catch (\Throwable $e) {
            Log::error('State update failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update state')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $state = State::findOrFail($id);
            $state->delete();

            return redirect()->back()->with('success', 'State deleted successfully');
        } catch (\Throwable $e) {
            Log::error('State deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete state. It may have associated cities.');
        }
    }

    public function toggleStatus($id)
    {
        try {
            $state = State::findOrFail($id);
            $state->is_active = !$state->is_active;
            $state->save();

            return redirect()->back()->with('success', 'State status updated successfully');
        } catch (\Throwable $e) {
            Log::error('State toggle failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update state status');
        }
    }

    public function downloadTemplate()
    {
        try {
            $zoneIds = RoleAccessHelper::getAccessibleZoneIds();
            $zones = Zone::whereIn('id', $zoneIds)
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name')
                ->toArray();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Headers
            $sheet->setCellValue('A1', 'Zone Name');
            $sheet->setCellValue('B1', 'State Name');

            // Styling
            $sheet->getStyle('A1:B1')->getFont()->setBold(true);
            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension('B')->setAutoSize(true);

            // Create dropdown for zones
            $zoneList = '"' . implode(',', $zones) . '"';

            for ($row = 2; $row <= 1000; $row++) {
                $validation = $sheet->getCell('A' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setErrorTitle('Invalid Zone');
                $validation->setError('Please select a zone from the dropdown');
                $validation->setPromptTitle('Select Zone');
                $validation->setPrompt('Choose a zone from the list');
                $validation->setFormula1($zoneList);
            }

            $writer = new Xlsx($spreadsheet);
            $filename = 'state_template_' . now()->format('Ymd_His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename={$filename}");
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;
        } catch (\Throwable $e) {
            Log::error('State template download failed: ' . $e->getMessage());
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
                $zoneName = trim($row['A'] ?? '');
                $stateName = trim($row['B'] ?? '');

                if (!$zoneName || !$stateName) {
                    $errors[] = "Row " . ($index + 2) . ": Both zone and state names are required";
                    continue;
                }

                // Find zone
                $zone = Zone::whereRaw('LOWER(name) = ?', [strtolower($zoneName)])->first();
                if (!$zone) {
                    $errors[] = "Row " . ($index + 2) . ": Zone '{$zoneName}' not found";
                    continue;
                }

                // Check if state already exists in this zone
                $exists = State::where('zone_id', $zone->id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($stateName)])
                    ->first();

                if ($exists) {
                    $errors[] = "Row " . ($index + 2) . ": State '{$stateName}' already exists in {$zoneName}";
                    continue;
                }

                State::create([
                    'zone_id' => $zone->id,
                    'name' => $stateName,
                    'is_active' => true,
                ]);

                $imported++;
            }

            $message = "{$imported} states imported successfully";
            if (count($errors) > 0) {
                $message .= ". " . count($errors) . " rows skipped.";
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('State upload failed: ' . $e->getMessage());
            return back()->with('error', 'Excel upload failed: ' . $e->getMessage());
        }
    }
}
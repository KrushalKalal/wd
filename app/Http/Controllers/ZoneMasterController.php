<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use App\Helpers\RoleAccessHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ZoneMasterController extends Controller
{
    public function index(Request $request)
    {
        $query = Zone::query();

        // Search
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $zones = $query->orderBy('name')->paginate($perPage);

        return Inertia::render('ZoneMaster/Index', [
            'records' => $zones,
            'filters' => [
                'search' => $request->search,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('ZoneMaster/Form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:zones,name',
        ]);

        try {
            Zone::create([
                'name' => $request->name,
                'is_active' => true,
            ]);

            return redirect()->route('zone-master.index')
                ->with('success', 'Zone added successfully');
        } catch (\Throwable $e) {
            Log::error('Zone creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to add zone')
                ->withInput();
        }
    }

    public function edit($id)
    {
        $zone = Zone::findOrFail($id);

        return Inertia::render('ZoneMaster/Form', [
            'zone' => $zone,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:zones,name,' . $id,
        ]);

        try {
            $zone = Zone::findOrFail($id);
            $zone->update([
                'name' => $request->name,
            ]);

            return redirect()->route('zone-master.index')
                ->with('success', 'Zone updated successfully');
        } catch (\Throwable $e) {
            Log::error('Zone update failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update zone')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $zone = Zone::findOrFail($id);
            $zone->delete();

            return redirect()->back()->with('success', 'Zone deleted successfully');
        } catch (\Throwable $e) {
            Log::error('Zone deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete zone. It may have associated states.');
        }
    }

    public function toggleStatus($id)
    {
        try {
            $zone = Zone::findOrFail($id);
            $zone->is_active = !$zone->is_active;
            $zone->save();

            return redirect()->back()->with('success', 'Zone status updated successfully');
        } catch (\Throwable $e) {
            Log::error('Zone toggle failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update zone status');
        }
    }

    public function downloadTemplate()
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'Zone Name');
            $sheet->getStyle('A1')->getFont()->setBold(true);
            $sheet->getColumnDimension('A')->setAutoSize(true);

            $writer = new Xlsx($spreadsheet);
            $filename = 'zone_template_' . now()->format('Ymd_His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename={$filename}");
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;
        } catch (\Throwable $e) {
            Log::error('Zone template download failed: ' . $e->getMessage());
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

                if (!$zoneName) {
                    $errors[] = "Row " . ($index + 2) . ": Zone name is required";
                    continue;
                }

                $exists = Zone::whereRaw('LOWER(name) = ?', [strtolower($zoneName)])->first();
                if ($exists) {
                    $errors[] = "Row " . ($index + 2) . ": Zone '{$zoneName}' already exists";
                    continue;
                }

                Zone::create([
                    'name' => $zoneName,
                    'is_active' => true,
                ]);

                $imported++;
            }

            $message = "{$imported} zones imported successfully";
            if (count($errors) > 0) {
                $message .= ". " . count($errors) . " rows skipped.";
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Zone upload failed: ' . $e->getMessage());
            return back()->with('error', 'Excel upload failed: ' . $e->getMessage());
        }
    }

    // API endpoint to get states by zone
    public function getStatesByZone($zoneId)
    {
        $states = \App\Models\State::where('zone_id', $zoneId)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($states);
    }
}
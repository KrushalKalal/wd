<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StateMasterController extends Controller
{
    public function index(Request $request)
    {
        $query = State::query();

        // Search
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $states = $query->orderBy('name')->paginate($perPage);

        return Inertia::render('StateMaster/Index', [
            'records' => $states,
            'filters' => [
                'search' => $request->search,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('StateMaster/Form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:states,name',
        ]);

        try {
            State::create([
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

        return Inertia::render('StateMaster/Form', [
            'state' => $state,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:states,name,' . $id,
        ]);

        try {
            $state = State::findOrFail($id);
            $state->update([
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
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Header
            $sheet->setCellValue('A1', 'State Name');

            // Styling
            $sheet->getStyle('A1')->getFont()->setBold(true);
            $sheet->getColumnDimension('A')->setAutoSize(true);

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
                $stateName = trim($row['A'] ?? '');

                if (!$stateName) {
                    $errors[] = "Row " . ($index + 2) . ": State name is required";
                    continue;
                }

                // Check if already exists
                $exists = State::whereRaw('LOWER(name) = ?', [strtolower($stateName)])->first();
                if ($exists) {
                    $errors[] = "Row " . ($index + 2) . ": State '{$stateName}' already exists";
                    continue;
                }


                State::create([
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
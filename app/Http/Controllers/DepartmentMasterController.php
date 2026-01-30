<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DepartmentMasterController extends Controller
{
    public function index(Request $request)
    {
        $query = Department::query();

        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $perPage = $request->get('per_page', 10);
        $departments = $query->orderBy('name')->paginate($perPage);

        return Inertia::render('DepartmentMaster/Index', [
            'records' => $departments,
            'filters' => [
                'search' => $request->search,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('DepartmentMaster/Form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
        ]);

        try {
            $data = $request->all();
            $data['is_active'] = true;
            Department::create($data);
            return redirect()->route('department-master.index')
                ->with('success', 'Department added successfully');
        } catch (\Throwable $e) {
            Log::error('Department creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to add department')
                ->withInput();
        }
    }

    public function edit($id)
    {
        $department = Department::findOrFail($id);
        return Inertia::render('DepartmentMaster/Form', [
            'department' => $department,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,' . $id,
        ]);

        try {
            $department = Department::findOrFail($id);
            $department->update(['name' => $request->name]);
            return redirect()->route('department-master.index')
                ->with('success', 'Department updated successfully');
        } catch (\Throwable $e) {
            Log::error('Department update failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update department')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $department = Department::findOrFail($id);
            $department->delete();
            return redirect()->back()->with('success', 'Department deleted successfully');
        } catch (\Throwable $e) {
            Log::error('Department deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete department');
        }
    }

    public function toggleStatus($id)
    {
        try {
            $department = Department::findOrFail($id);
            $department->is_active = !$department->is_active;
            $department->save();

            return redirect()->back()->with('success', 'Department status updated successfully');
        } catch (\Throwable $e) {
            Log::error('Department toggle failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update department status');
        }
    }

    public function downloadTemplate()
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'Department Name');
            $sheet->getStyle('A1')->getFont()->setBold(true);
            $sheet->getColumnDimension('A')->setAutoSize(true);

            $writer = new Xlsx($spreadsheet);
            $filename = 'department_template_' . now()->format('Ymd_His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename={$filename}");
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;
        } catch (\Throwable $e) {
            Log::error('Department template download failed: ' . $e->getMessage());
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
                $name = trim($row['A'] ?? '');

                if (!$name) {
                    $errors[] = "Row " . ($index + 2) . ": Department name is required";
                    continue;
                }

                $exists = Department::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
                if ($exists) {
                    $errors[] = "Row " . ($index + 2) . ": Department '{$name}' already exists";
                    continue;
                }

                Department::create(['name' => $name, 'is_active' => true,]);
                $imported++;
            }

            $message = "{$imported} departments imported successfully";
            if (count($errors) > 0) {
                $message .= ". " . count($errors) . " rows skipped.";
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Department upload failed: ' . $e->getMessage());
            return back()->with('error', 'Excel upload failed: ' . $e->getMessage());
        }
    }
}
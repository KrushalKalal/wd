<?php

namespace App\Http\Controllers;

use App\Models\CategoryOne;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CategoryOneMasterController extends Controller
{
    public function index(Request $request)
    {
        $query = CategoryOne::query();

        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $perPage = $request->get('per_page', 10);
        $categories = $query->orderBy('name')->paginate($perPage);

        return Inertia::render('CategoryOneMaster/Index', [
            'records' => $categories,
            'filters' => [
                'search' => $request->search,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('CategoryOneMaster/Form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:category_one,name',
        ]);

        try {
            $data = $request->all();
            $data['is_active'] = true;
            CategoryOne::create($data);
            return redirect()->route('category-one-master.index')
                ->with('success', 'Category One added successfully');
        } catch (\Throwable $e) {
            Log::error('Category One creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to add category')
                ->withInput();
        }
    }

    public function edit($id)
    {
        $category = CategoryOne::findOrFail($id);
        return Inertia::render('CategoryOneMaster/Form', [
            'categoryOne' => $category,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:category_one,name,' . $id,
        ]);

        try {
            $category = CategoryOne::findOrFail($id);
            $category->update(['name' => $request->name]);
            return redirect()->route('category-one-master.index')
                ->with('success', 'Category One updated successfully');
        } catch (\Throwable $e) {
            Log::error('Category One update failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update category')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $category = CategoryOne::findOrFail($id);
            $category->delete();
            return redirect()->back()->with('success', 'Category One deleted successfully');
        } catch (\Throwable $e) {
            Log::error('Category One deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete category');
        }
    }

    public function toggleStatus($id)
    {
        try {
            $category = CategoryOne::findOrFail($id);
            $category->is_active = !$category->is_active;
            $category->save();

            return redirect()->back()->with('success', 'CategoryOne status updated successfully');
        } catch (\Throwable $e) {
            Log::error('CategoryOne toggle failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update categoryone status');
        }
    }

    public function downloadTemplate()
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'Category One Name');
            $sheet->getStyle('A1')->getFont()->setBold(true);
            $sheet->getColumnDimension('A')->setAutoSize(true);

            $writer = new Xlsx($spreadsheet);
            $filename = 'category_one_template_' . now()->format('Ymd_His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename={$filename}");
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;
        } catch (\Throwable $e) {
            Log::error('Category One template download failed: ' . $e->getMessage());
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
                    $errors[] = "Row " . ($index + 2) . ": Category name is required";
                    continue;
                }

                $exists = CategoryOne::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
                if ($exists) {
                    $errors[] = "Row " . ($index + 2) . ": Category '{$name}' already exists";
                    continue;
                }

                CategoryOne::create(['name' => $name, 'is_active' => true,]);
                $imported++;
            }

            $message = "{$imported} categories imported successfully";
            if (count($errors) > 0) {
                $message .= ". " . count($errors) . " rows skipped.";
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Category One upload failed: ' . $e->getMessage());
            return back()->with('error', 'Excel upload failed: ' . $e->getMessage());
        }
    }
}
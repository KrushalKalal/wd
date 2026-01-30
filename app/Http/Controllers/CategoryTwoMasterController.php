<?php

namespace App\Http\Controllers;

use App\Models\CategoryTwo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CategoryTwoMasterController extends Controller
{
    public function index(Request $request)
    {
        $query = CategoryTwo::query();

        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $perPage = $request->get('per_page', 10);
        $categories = $query->orderBy('name')->paginate($perPage);

        return Inertia::render('CategoryTwoMaster/Index', [
            'records' => $categories,
            'filters' => [
                'search' => $request->search,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('CategoryTwoMaster/Form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:category_two,name',
        ]);

        try {
            $data = $request->all();
            $data['is_active'] = true;
            CategoryTwo::create($data);
            return redirect()->route('category-two-master.index')
                ->with('success', 'Category Two added successfully');
        } catch (\Throwable $e) {
            Log::error('Category Two creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to add category')
                ->withInput();
        }
    }

    public function edit($id)
    {
        $category = CategoryTwo::findOrFail($id);
        return Inertia::render('CategoryTwoMaster/Form', [
            'CategoryTwo' => $category,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:category_two,name,' . $id,
        ]);

        try {
            $category = CategoryTwo::findOrFail($id);
            $category->update(['name' => $request->name]);
            return redirect()->route('category-two-master.index')
                ->with('success', 'Category Two updated successfully');
        } catch (\Throwable $e) {
            Log::error('Category Two update failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update category')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $category = CategoryTwo::findOrFail($id);
            $category->delete();
            return redirect()->back()->with('success', 'Category Two deleted successfully');
        } catch (\Throwable $e) {
            Log::error('Category Two deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete category');
        }
    }

    public function toggleStatus($id)
    {
        try {
            $category = CategoryTwo::findOrFail($id);
            $category->is_active = !$category->is_active;
            $category->save();

            return redirect()->back()->with('success', 'CategoryTwo status updated successfully');
        } catch (\Throwable $e) {
            Log::error('CategoryTwo toggle failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update categorytwo status');
        }
    }

    public function downloadTemplate()
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'Category Two Name');
            $sheet->getStyle('A1')->getFont()->setBold(true);
            $sheet->getColumnDimension('A')->setAutoSize(true);

            $writer = new Xlsx($spreadsheet);
            $filename = 'category_two_template_' . now()->format('Ymd_His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename={$filename}");
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;
        } catch (\Throwable $e) {
            Log::error('Category Two template download failed: ' . $e->getMessage());
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

                $exists = CategoryTwo::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
                if ($exists) {
                    $errors[] = "Row " . ($index + 2) . ": Category '{$name}' already exists";
                    continue;
                }

                CategoryTwo::create(['name' => $name, 'is_active' => true,]);
                $imported++;
            }

            $message = "{$imported} categories imported successfully";
            if (count($errors) > 0) {
                $message .= ". " . count($errors) . " rows skipped.";
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Category Two upload failed: ' . $e->getMessage());
            return back()->with('error', 'Excel upload failed: ' . $e->getMessage());
        }
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProductCategoryMasterController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductCategory::query();

        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $perPage = $request->get('per_page', 10);
        $categories = $query->orderBy('name')->paginate($perPage);

        return Inertia::render('ProductCategoryMaster/Index', [
            'records' => $categories,
            'filters' => [
                'search' => $request->search,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('ProductCategoryMaster/Form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:product_categories,name',
        ]);

        try {
            $data = $request->all();
            $data['is_active'] = true;
            ProductCategory::create($data);
            return redirect()->route('product-category-master.index')
                ->with('success', 'Product Category added successfully');
        } catch (\Throwable $e) {
            Log::error('Product Category creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to add category')
                ->withInput();
        }
    }

    public function edit($id)
    {
        $category = ProductCategory::findOrFail($id);
        return Inertia::render('ProductCategoryMaster/Form', [
            'ProductCategory' => $category,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:product_categories,name,' . $id,
        ]);

        try {
            $category = ProductCategory::findOrFail($id);
            $category->update(['name' => $request->name]);
            return redirect()->route('product-category-master.index')
                ->with('success', 'Product Category updated successfully');
        } catch (\Throwable $e) {
            Log::error('Product Category update failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update category')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $category = ProductCategory::findOrFail($id);
            $category->delete();
            return redirect()->back()->with('success', 'Product Category deleted successfully');
        } catch (\Throwable $e) {
            Log::error('Product Category deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete category');
        }
    }

    public function toggleStatus($id)
    {
        try {
            $category = ProductCategory::findOrFail($id);
            $category->is_active = !$category->is_active;
            $category->save();

            return redirect()->back()->with('success', 'ProductCategory status updated successfully');
        } catch (\Throwable $e) {
            Log::error('ProductCategory toggle failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update productcategory status');
        }
    }

    public function downloadTemplate()
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'Product Category Name');
            $sheet->getStyle('A1')->getFont()->setBold(true);
            $sheet->getColumnDimension('A')->setAutoSize(true);

            $writer = new Xlsx($spreadsheet);
            $filename = 'product_categories_template_' . now()->format('Ymd_His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename={$filename}");
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;
        } catch (\Throwable $e) {
            Log::error('Product Category template download failed: ' . $e->getMessage());
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

                $exists = ProductCategory::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
                if ($exists) {
                    $errors[] = "Row " . ($index + 2) . ": Category '{$name}' already exists";
                    continue;
                }

                ProductCategory::create(['name' => $name, 'is_active' => true,]);
                $imported++;
            }

            $message = "{$imported} categories imported successfully";
            if (count($errors) > 0) {
                $message .= ". " . count($errors) . " rows skipped.";
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Product Category upload failed: ' . $e->getMessage());
            return back()->with('error', 'Excel upload failed: ' . $e->getMessage());
        }
    }
}
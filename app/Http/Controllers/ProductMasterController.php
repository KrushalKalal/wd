<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\CategoryOne;
use App\Models\CategoryTwo;
use App\Models\CategoryThree;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class ProductMasterController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['categoryOne', 'categoryTwo', 'categoryThree', 'pCategory']);

        // Search
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by category one
        if ($request->has('category_one_id') && $request->category_one_id) {
            $query->where('category_one_id', $request->category_one_id);
        }

        // Filter by category two
        if ($request->has('category_two_id') && $request->category_two_id) {
            $query->where('category_two_id', $request->category_two_id);
        }

        // Filter by category three
        if ($request->has('category_three_id') && $request->category_three_id) {
            $query->where('category_three_id', $request->category_three_id);
        }

        // Filter by product category
        if ($request->has('p_category_id') && $request->p_category_id) {
            $query->where('p_category_id', $request->p_category_id);
        }

        $perPage = $request->get('per_page', 10);
        $products = $query->orderBy('name')->paginate($perPage);

        $categoryOnes = CategoryOne::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryTwos = CategoryTwo::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryThrees = CategoryThree::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $productCategories = ProductCategory::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('ProductMaster/Index', [
            'records' => $products,
            'categoryOnes' => $categoryOnes,
            'categoryTwos' => $categoryTwos,
            'categoryThrees' => $categoryThrees,
            'productCategories' => $productCategories,
            'filters' => [
                'search' => $request->search,
                'category_one_id' => $request->category_one_id,
                'category_two_id' => $request->category_two_id,
                'category_three_id' => $request->category_three_id,
                'p_category_id' => $request->p_category_id,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create()
    {
        $categoryOnes = CategoryOne::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryTwos = CategoryTwo::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryThrees = CategoryThree::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $productCategories = ProductCategory::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('ProductMaster/Form', [
            'categoryOnes' => $categoryOnes,
            'categoryTwos' => $categoryTwos,
            'categoryThrees' => $categoryThrees,
            'productCategories' => $productCategories,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_one_id' => 'required|exists:category_one,id',
            'category_two_id' => 'required|exists:category_two,id',
            'category_three_id' => 'required|exists:category_three,id',
            'p_category_id' => 'required|exists:product_categories,id',
            'mrp' => 'required|numeric|min:0',
            'edo' => 'nullable|date',
            'total_stock' => 'nullable|integer|min:0',
            'catalogue_pdf' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        try {
            $data = $request->except('catalogue_pdf');
            $data['is_active'] = true;

            if ($request->hasFile('catalogue_pdf')) {
                $productName = $request->input('name');
                $productFolder = 'products/' . Str::slug($productName);

                // Store file in the product-specific folder
                $file = $request->file('catalogue_pdf');
                $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.pdf';
                $pdfPath = $file->storeAs($productFolder, $fileName, 'public');

                $data['catalogue_pdf'] = $pdfPath;
            }

            Product::create($data);

            return redirect()->route('product-master.index')
                ->with('success', 'Product added successfully');
        } catch (\Throwable $e) {
            Log::error('Product creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to add product')
                ->withInput();
        }
    }

    public function edit($id)
    {
        $product = Product::with(['categoryOne', 'categoryTwo', 'categoryThree', 'pCategory'])
            ->findOrFail($id);


        $categoryOnes = CategoryOne::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryTwos = CategoryTwo::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryThrees = CategoryThree::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $productCategories = ProductCategory::where('is_active', true)->select('id', 'name')->orderBy('name')->get();


        return Inertia::render('ProductMaster/Form', [
            'product' => $product,
            'categoryOnes' => $categoryOnes,
            'categoryTwos' => $categoryTwos,
            'categoryThrees' => $categoryThrees,
            'productCategories' => $productCategories,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_one_id' => 'required|exists:category_one,id',
            'category_two_id' => 'required|exists:category_two,id',
            'category_three_id' => 'required|exists:category_three,id',
            'p_category_id' => 'required|exists:product_categories,id',
            'mrp' => 'required|numeric|min:0',
            'edo' => 'nullable|date',
            'total_stock' => 'nullable|integer|min:0',
            'catalogue_pdf' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        try {
            $product = Product::findOrFail($id);
            $data = $request->except('catalogue_pdf');

            // Handle PDF upload
            if ($request->hasFile('catalogue_pdf')) {
                // Delete old PDF if exists
                if ($product->catalogue_pdf && Storage::disk('public')->exists($product->catalogue_pdf)) {
                    Storage::disk('public')->delete($product->catalogue_pdf);
                }

                $productName = $request->input('name');
                $productFolder = 'products/' . Str::slug($productName);

                // Store new file in the product-specific folder
                $file = $request->file('catalogue_pdf');
                $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.pdf';
                $pdfPath = $file->storeAs($productFolder, $fileName, 'public');

                $data['catalogue_pdf'] = $pdfPath;
            }

            $product->update($data);


            return redirect()->route('product-master.index')
                ->with('success', 'Product updated successfully');
        } catch (\Throwable $e) {
            Log::error('Product update failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update product')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);

            // Delete associated PDF if exists
            if ($product->catalogue_pdf && Storage::disk('public')->exists($product->catalogue_pdf)) {
                Storage::disk('public')->delete($product->catalogue_pdf);

                // Optionally, delete the entire product folder if empty
                $folderPath = dirname($product->catalogue_pdf);
                $files = Storage::disk('public')->files($folderPath);
                if (empty($files)) {
                    Storage::disk('public')->deleteDirectory($folderPath);
                }
            }

            $product->delete();

            return redirect()->back()->with('success', 'Product deleted successfully');
        } catch (\Throwable $e) {
            Log::error('Product deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete product');
        }
    }

    public function toggleStatus($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->is_active = !$product->is_active;
            $product->save();

            return redirect()->back()->with('success', 'Product status updated successfully');
        } catch (\Throwable $e) {
            Log::error('Product toggle failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update product status');
        }
    }

    public function downloadTemplate()
    {
        try {
            $categoryOnes = CategoryOne::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
            $categoryTwos = CategoryTwo::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
            $categoryThrees = CategoryThree::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
            $productCategories = ProductCategory::where('is_active', true)->orderBy('name')->pluck('name')->toArray();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Products');

            // Headers
            $headers = [
                'Product Name',
                'Category One',
                'Category Two',
                'Category Three',
                'Product Category',
                'MRP',
                'EDO (YYYY-MM-DD)',
                'Total Stock'
            ];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '1', $header);
                $col++;
            }

            // Styling
            $headerStyle = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE0E0E0']
                ]
            ];
            $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

            // Create dropdown lists
            $cat1List = '"' . implode(',', $categoryOnes) . '"';
            $cat2List = '"' . implode(',', $categoryTwos) . '"';
            $cat3List = '"' . implode(',', $categoryThrees) . '"';
            $pCatList = '"' . implode(',', $productCategories) . '"';

            // Add dropdowns
            for ($row = 2; $row <= 1000; $row++) {
                // Category One dropdown (Column B)
                $validation = $sheet->getCell('B' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                $validation->setFormula1($cat1List);

                // Category Two dropdown (Column C)
                $validation = $sheet->getCell('C' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                $validation->setFormula1($cat2List);

                // Category Three dropdown (Column D)
                $validation = $sheet->getCell('D' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                $validation->setFormula1($cat3List);

                // Product Category dropdown (Column E)
                $validation = $sheet->getCell('E' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                $validation->setFormula1($pCatList);

                // Default stock
                $sheet->setCellValue('H' . $row, 0);
            }

            // Auto-size columns
            foreach (range('A', 'H') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $filename = 'product_template_' . now()->format('Ymd_His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename={$filename}");
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;
        } catch (\Throwable $e) {
            Log::error('Product template download failed: ' . $e->getMessage());
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
                $productName = trim($row['A'] ?? '');
                $cat1Name = trim($row['B'] ?? '');
                $cat2Name = trim($row['C'] ?? '');
                $cat3Name = trim($row['D'] ?? '');
                $pCatName = trim($row['E'] ?? '');
                $mrp = trim($row['F'] ?? '');
                $edo = trim($row['G'] ?? '');
                $totalStock = trim($row['H'] ?? 0);

                if (!$productName || !$cat1Name || !$cat2Name || !$cat3Name || !$pCatName || !$mrp) {
                    $errors[] = "Row " . ($index + 2) . ": Product name, all categories, and MRP are required";
                    continue;
                }

                // Find categories
                $cat1 = CategoryOne::whereRaw('LOWER(name) = ?', [strtolower($cat1Name)])->first();
                if (!$cat1) {
                    $errors[] = "Row " . ($index + 2) . ": Category One '{$cat1Name}' not found";
                    continue;
                }

                $cat2 = CategoryTwo::whereRaw('LOWER(name) = ?', [strtolower($cat2Name)])->first();
                if (!$cat2) {
                    $errors[] = "Row " . ($index + 2) . ": Category Two '{$cat2Name}' not found";
                    continue;
                }

                $cat3 = CategoryThree::whereRaw('LOWER(name) = ?', [strtolower($cat3Name)])->first();
                if (!$cat3) {
                    $errors[] = "Row " . ($index + 2) . ": Category Three '{$cat3Name}' not found";
                    continue;
                }

                $pCat = ProductCategory::whereRaw('LOWER(name) = ?', [strtolower($pCatName)])->first();
                if (!$pCat) {
                    $errors[] = "Row " . ($index + 2) . ": Product Category '{$pCatName}' not found";
                    continue;
                }

                Product::create([
                    'name' => $productName,
                    'category_one_id' => $cat1->id,
                    'category_two_id' => $cat2->id,
                    'category_three_id' => $cat3->id,
                    'p_category_id' => $pCat->id,
                    'mrp' => $mrp,
                    'edo' => $edo ?: null,
                    'total_stock' => $totalStock ?: 0,
                    'is_active' => true,
                    'catalogue_pdf' => null
                ]);

                $imported++;
            }

            $message = "{$imported} products imported successfully";
            if (count($errors) > 0) {
                $message .= ". " . count($errors) . " rows skipped.";
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Product upload failed: ' . $e->getMessage());
            return back()->with('error', 'Excel upload failed: ' . $e->getMessage());
        }
    }
}
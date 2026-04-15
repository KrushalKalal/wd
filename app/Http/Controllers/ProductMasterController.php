<?php

namespace App\Http\Controllers;

use App\Helpers\RoleAccessHelper;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\State;
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
        $query = Product::with(['pCategory', 'state']);

        $query = RoleAccessHelper::applyRoleFilter($query);

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('p_category_id') && $request->p_category_id) {
            $query->where('p_category_id', $request->p_category_id);
        }

        if ($request->has('state_id') && $request->state_id) {
            $query->where('state_id', $request->state_id);
        }

        $perPage = $request->get('per_page', 10);
        $products = $query->orderBy('name')->paginate($perPage);

        $productCategories = ProductCategory::where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $stateIds = RoleAccessHelper::getAccessibleStateIds();
        $states = State::whereIn('id', $stateIds)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('ProductMaster/Index', [
            'records' => $products,
            'productCategories' => $productCategories,
            'states' => $states,
            'filters' => [
                'search' => $request->search,
                'p_category_id' => $request->p_category_id,
                'state_id' => $request->state_id,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create()
    {
        $productCategories = ProductCategory::where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $stateIds = RoleAccessHelper::getAccessibleStateIds();
        $states = State::whereIn('id', $stateIds)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $userLocation = RoleAccessHelper::getUserLocation();
        $locationLocks = RoleAccessHelper::getLocationLocks();

        return Inertia::render('ProductMaster/Form', [
            'productCategories' => $productCategories,
            'states' => $states,
            'userLocation' => $userLocation,
            'locationLocks' => $locationLocks,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku',
            'p_category_id' => 'required|exists:product_categories,id',
            'mrp' => 'required|numeric|min:0',
            'edd' => 'nullable|numeric|min:0',
            'pack_size' => 'nullable|integer|min:0',
            'volume' => 'nullable|integer|min:0',
            'state_id' => 'nullable|exists:states,id',
            'total_stock' => 'nullable|integer|min:0',
            'image' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            // 'catalogue_pdf' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        try {
            $data = $request->except(['image']);
            // $data = $request->except(['catalogue_pdf', 'image']);
            $data['is_active'] = true;

            // Handle image upload
            if ($request->hasFile('image')) {
                $folder = 'products/' . Str::slug($request->name);
                $file = $request->file('image');
                $fileName = 'image_' . time() . '.' . $file->getClientOriginalExtension();
                $data['image'] = $file->storeAs($folder, $fileName, 'public');
            }

            // Handle catalogue PDF upload — commented out
            // if ($request->hasFile('catalogue_pdf')) {
            //     $folder = 'products/' . Str::slug($request->name);
            //     $file = $request->file('catalogue_pdf');
            //     $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.pdf';
            //     $data['catalogue_pdf'] = $file->storeAs($folder, $fileName, 'public');
            // }

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
        $product = Product::with(['pCategory', 'state'])->findOrFail($id);

        $productCategories = ProductCategory::where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $stateIds = RoleAccessHelper::getAccessibleStateIds();
        $states = State::whereIn('id', $stateIds)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $userLocation = RoleAccessHelper::getUserLocation();
        $locationLocks = RoleAccessHelper::getLocationLocks();

        return Inertia::render('ProductMaster/Form', [
            'product' => $product,
            'productCategories' => $productCategories,
            'states' => $states,
            'userLocation' => $userLocation,
            'locationLocks' => $locationLocks,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku,' . $id,
            'p_category_id' => 'required|exists:product_categories,id',
            'mrp' => 'required|numeric|min:0',
            'edd' => 'nullable|numeric|min:0',
            'pack_size' => 'nullable|integer|min:0',
            'volume' => 'nullable|integer|min:0',
            'state_id' => 'nullable|exists:states,id',
            'total_stock' => 'nullable|integer|min:0',
            'image' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            // 'catalogue_pdf' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        try {
            $product = Product::findOrFail($id);
            $data = $request->except(['image']);
            // $data = $request->except(['catalogue_pdf', 'image']);

            // Handle image upload
            if ($request->hasFile('image')) {
                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                }
                $folder = 'products/' . Str::slug($request->name);
                $file = $request->file('image');
                $fileName = 'image_' . time() . '.' . $file->getClientOriginalExtension();
                $data['image'] = $file->storeAs($folder, $fileName, 'public');
            }

            // Handle catalogue PDF upload — commented out
            // if ($request->hasFile('catalogue_pdf')) {
            //     if ($product->catalogue_pdf && Storage::disk('public')->exists($product->catalogue_pdf)) {
            //         Storage::disk('public')->delete($product->catalogue_pdf);
            //     }
            //     $folder = 'products/' . Str::slug($request->name);
            //     $file = $request->file('catalogue_pdf');
            //     $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.pdf';
            //     $data['catalogue_pdf'] = $file->storeAs($folder, $fileName, 'public');
            // }

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

            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            // Catalogue PDF delete — commented out
            // if ($product->catalogue_pdf && Storage::disk('public')->exists($product->catalogue_pdf)) {
            //     Storage::disk('public')->delete($product->catalogue_pdf);
            // }

            $folderPath = 'products/' . Str::slug($product->name);
            if (Storage::disk('public')->exists($folderPath)) {
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
            $productCategories = ProductCategory::where('is_active', true)
                ->orderBy('name')
                ->pluck('name')
                ->toArray();

            $stateIds = RoleAccessHelper::getAccessibleStateIds();
            $states = State::whereIn('id', $stateIds)
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name')
                ->toArray();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Products');

            $headers = [
                'Product Name *',
                'SKU *',
                'Product Category *',
                'Price (MRP) *',
                'Pack Size',
                'Volume',
                'State',
                'EDD',
                'Total Stock',
            ];

            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '1', $header);
                $col++;
            }

            $headerStyle = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE0E0E0'],
                ],
            ];
            $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

            $pCatList = '"' . implode(',', $productCategories) . '"';
            $stateList = '"' . implode(',', $states) . '"';

            for ($row = 2; $row <= 1000; $row++) {
                $validation = $sheet->getCell('C' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                $validation->setFormula1($pCatList);

                $validation = $sheet->getCell('G' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1($stateList);

                $sheet->setCellValue('I' . $row, 0);
            }

            foreach (range('A', 'I') as $col) {
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
                $name = trim($row['A'] ?? '');
                $sku = trim($row['B'] ?? '');
                $pCatName = trim($row['C'] ?? '');
                $mrp = trim($row['D'] ?? '');
                $packSize = trim($row['E'] ?? '');
                $volume = trim($row['F'] ?? '');
                $stateName = trim($row['G'] ?? '');
                $edd = trim($row['H'] ?? '');
                $totalStock = trim($row['I'] ?? 0);

                if (!$name || !$sku || !$pCatName || !$mrp) {
                    $errors[] = "Row " . ($index + 2) . ": Name, SKU, Category and Price are required";
                    continue;
                }

                $skuExists = Product::where('sku', $sku)->first();
                if ($skuExists) {
                    $errors[] = "Row " . ($index + 2) . ": SKU '{$sku}' already exists";
                    continue;
                }

                $pCat = ProductCategory::whereRaw('LOWER(name) = ?', [strtolower($pCatName)])->first();
                if (!$pCat) {
                    $errors[] = "Row " . ($index + 2) . ": Product Category '{$pCatName}' not found";
                    continue;
                }

                $stateId = null;
                if ($stateName) {
                    $state = State::whereRaw('LOWER(name) = ?', [strtolower($stateName)])->first();
                    $stateId = $state?->id;
                }

                Product::create([
                    'name' => $name,
                    'sku' => $sku,
                    'p_category_id' => $pCat->id,
                    'mrp' => $mrp,
                    'pack_size' => $packSize ?: null,
                    'volume' => $volume ?: null,
                    'state_id' => $stateId,
                    'edd' => $edd ?: null,
                    'total_stock' => $totalStock ?: 0,
                    'is_active' => true,
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
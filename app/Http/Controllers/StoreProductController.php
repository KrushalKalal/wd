<?php

namespace App\Http\Controllers;

use App\Models\StoreProduct;
use App\Models\Store;
use App\Models\Product;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class StoreProductController extends Controller
{
    public function index(Request $request)
    {
        $query = StoreProduct::with([
            'store.state',
            'store.city',
            'store.area',
            'product'
        ]);

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('store', function ($storeQuery) use ($request) {
                    $storeQuery->where('name', 'like', '%' . $request->search . '%');
                })->orWhereHas('product', function ($productQuery) use ($request) {
                    $productQuery->where('name', 'like', '%' . $request->search . '%');
                });
            });
        }

        // Filter by store
        if ($request->has('store_id') && $request->store_id) {
            $query->where('store_id', $request->store_id);
        }

        // Filter by product
        if ($request->has('product_id') && $request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by state (via store)
        if ($request->has('state_id') && $request->state_id) {
            $query->whereHas('store', function ($q) use ($request) {
                $q->where('state_id', $request->state_id);
            });
        }

        // Filter by city (via store)
        if ($request->has('city_id') && $request->city_id) {
            $query->whereHas('store', function ($q) use ($request) {
                $q->where('city_id', $request->city_id);
            });
        }

        // Filter by area (via store)
        if ($request->has('area_id') && $request->area_id) {
            $query->whereHas('store', function ($q) use ($request) {
                $q->where('area_id', $request->area_id);
            });
        }

        $perPage = $request->get('per_page', 10);
        $storeProducts = $query->orderBy('id', 'desc')->paginate($perPage);

        $stores = Store::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $products = Product::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $states = State::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('StoreProduct/Index', [
            'records' => $storeProducts,
            'stores' => $stores,
            'products' => $products,
            'states' => $states,
            'filters' => [
                'search' => $request->search,
                'store_id' => $request->store_id,
                'product_id' => $request->product_id,
                'state_id' => $request->state_id,
                'city_id' => $request->city_id,
                'area_id' => $request->area_id,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create()
    {
        $stores = Store::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $products = Product::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('StoreProduct/Form', [
            'stores' => $stores,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'product_id' => 'required|exists:products,id',
            'current_stock' => 'required|integer|min:0',
        ]);

        try {
            // Check if already exists
            $existing = StoreProduct::where('store_id', $request->store_id)
                ->where('product_id', $request->product_id)
                ->first();

            if ($existing) {
                return redirect()->back()
                    ->with('error', 'This product is already assigned to this store')
                    ->withInput();
            }

            StoreProduct::create([
                'store_id' => $request->store_id,
                'product_id' => $request->product_id,
                'current_stock' => $request->current_stock,
                'pending_stock' => 0,
                'return_stock' => 0,
            ]);

            return redirect()->route('store-product.index')
                ->with('success', 'Store product added successfully');
        } catch (\Throwable $e) {
            Log::error('Store product creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to add store product')
                ->withInput();
        }
    }

    public function edit($id)
    {
        $storeProduct = StoreProduct::with(['store', 'product'])->findOrFail($id);
        $stores = Store::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $products = Product::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('StoreProduct/Form', [
            'storeProduct' => $storeProduct,
            'stores' => $stores,
            'products' => $products,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'product_id' => 'required|exists:products,id',
            'current_stock' => 'required|integer|min:0',
        ]);

        try {
            $storeProduct = StoreProduct::findOrFail($id);

            // Check if combination already exists (excluding current record)
            $existing = StoreProduct::where('store_id', $request->store_id)
                ->where('product_id', $request->product_id)
                ->where('id', '!=', $id)
                ->first();

            if ($existing) {
                return redirect()->back()
                    ->with('error', 'This product is already assigned to this store')
                    ->withInput();
            }

            $storeProduct->update([
                'store_id' => $request->store_id,
                'product_id' => $request->product_id,
                'current_stock' => $request->current_stock,
            ]);

            return redirect()->route('store-product.index')
                ->with('success', 'Store product updated successfully');
        } catch (\Throwable $e) {
            Log::error('Store product update failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update store product')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $storeProduct = StoreProduct::findOrFail($id);
            $storeProduct->delete();

            return redirect()->back()->with('success', 'Store product deleted successfully');
        } catch (\Throwable $e) {
            Log::error('Store product deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete store product');
        }
    }

    public function downloadTemplate()
    {
        try {
            $stores = Store::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
            $products = Product::where('is_active', true)->orderBy('name')->pluck('name')->toArray();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Store Products');

            // Headers
            $headers = [
                'Store Name',
                'Product Name',
                'Current Stock'
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
            $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);

            // Create dropdown lists
            $storeList = '"' . implode(',', $stores) . '"';
            $productList = '"' . implode(',', $products) . '"';

            // Add dropdowns
            for ($row = 2; $row <= 1000; $row++) {
                // Store dropdown (Column A)
                $validation = $sheet->getCell('A' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                $validation->setFormula1($storeList);

                // Product dropdown (Column B)
                $validation = $sheet->getCell('B' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                $validation->setFormula1($productList);

                // Default stock
                $sheet->setCellValue('C' . $row, 0);
            }

            // Auto-size columns
            foreach (range('A', 'C') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $filename = 'store_product_template_' . now()->format('Ymd_His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename={$filename}");
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;
        } catch (\Throwable $e) {
            Log::error('Store product template download failed: ' . $e->getMessage());
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
                $storeName = trim($row['A'] ?? '');
                $productName = trim($row['B'] ?? '');
                $currentStock = trim($row['C'] ?? 0);

                if (!$storeName || !$productName) {
                    $errors[] = "Row " . ($index + 2) . ": Store name and product name are required";
                    continue;
                }

                // Find store
                $store = Store::where('is_active', true)
                    ->whereRaw('LOWER(name) = ?', [strtolower($storeName)])
                    ->first();
                if (!$store) {
                    $errors[] = "Row " . ($index + 2) . ": Store '{$storeName}' not found";
                    continue;
                }

                // Find product
                $product = Product::where('is_active', true)
                    ->whereRaw('LOWER(name) = ?', [strtolower($productName)])
                    ->first();
                if (!$product) {
                    $errors[] = "Row " . ($index + 2) . ": Product '{$productName}' not found";
                    continue;
                }

                // Check if already exists
                $existing = StoreProduct::where('store_id', $store->id)
                    ->where('product_id', $product->id)
                    ->first();

                if ($existing) {
                    // Update existing
                    $existing->update(['current_stock' => $currentStock]);
                } else {
                    // Create new
                    StoreProduct::create([
                        'store_id' => $store->id,
                        'product_id' => $product->id,
                        'current_stock' => $currentStock,
                        'pending_stock' => 0,
                        'return_stock' => 0,
                    ]);
                }

                $imported++;
            }

            $message = "{$imported} store products imported successfully";
            if (count($errors) > 0) {
                $message .= ". " . count($errors) . " rows skipped.";
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Store product upload failed: ' . $e->getMessage());
            return back()->with('error', 'Excel upload failed: ' . $e->getMessage());
        }
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\ProductCategory;
use App\Models\CategoryOne;
use App\Models\CategoryTwo;
use App\Models\CategoryThree;
use App\Models\State;
use App\Models\City;
use App\Models\Area;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class OfferMasterController extends Controller
{
    public function index(Request $request)
    {
        $query = Offer::with([
            'productCategory',
            'categoryOne',
            'categoryTwo',
            'categoryThree',
            'state',
            'city',
            'area',
        ])->where('is_active', true);

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('offer_title', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by offer type
        if ($request->has('offer_type') && $request->offer_type) {
            $query->where('offer_type', $request->offer_type);
        }

        $perPage = $request->get('per_page', 10);
        $offers = $query->orderBy('start_date', 'desc')->paginate($perPage);

        return Inertia::render('OfferMaster/Index', [
            'records' => $offers,
            'filters' => [
                'search' => $request->search,
                'offer_type' => $request->offer_type,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create()
    {
        $productCategories = ProductCategory::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryOnes = CategoryOne::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryTwos = CategoryTwo::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryThrees = CategoryThree::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $states = State::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('OfferMaster/Form', [
            'productCategories' => $productCategories,
            'categoryOnes' => $categoryOnes,
            'categoryTwos' => $categoryTwos,
            'categoryThrees' => $categoryThrees,
            'states' => $states,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'offer_type' => 'required|in:product_category,store_category,sales_volume,location',
            'offer_title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'offer_percentage' => 'required|numeric|min:0|max:100',
            'p_category_id' => 'nullable|exists:product_categories,id',
            'category_one_id' => 'nullable|exists:category_one,id',
            'category_two_id' => 'nullable|exists:category_two,id',
            'category_three_id' => 'nullable|exists:category_three,id',
            'store_ids' => 'nullable|array',
            'store_ids.*' => 'exists:stores,id',
            'min_sales_amount' => 'nullable|numeric|min:0',
            'max_sales_amount' => 'nullable|numeric|min:0',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $data = $request->all();
            $data['is_active'] = true;

            Offer::create($data);

            return redirect()->route('offer-master.index')
                ->with('success', 'Offer added successfully');
        } catch (\Throwable $e) {
            Log::error('Offer creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to add offer')
                ->withInput();
        }
    }

    public function edit($id)
    {
        $offer = Offer::with([
            'productCategory',
            'categoryOne',
            'categoryTwo',
            'categoryThree',
            'state',
            'city',
            'area'
        ])->findOrFail($id);

        $productCategories = ProductCategory::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryOnes = CategoryOne::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryTwos = CategoryTwo::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryThrees = CategoryThree::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $states = State::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        // Load cities if state is selected
        $cities = [];
        if ($offer->state_id) {
            $cities = City::where('state_id', $offer->state_id)
                ->where('is_active', true)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        }

        // Load areas if city is selected
        $areas = [];
        if ($offer->city_id) {
            $areas = Area::where('city_id', $offer->city_id)
                ->where('is_active', true)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        }

        return Inertia::render('OfferMaster/Form', [
            'offer' => $offer,
            'productCategories' => $productCategories,
            'categoryOnes' => $categoryOnes,
            'categoryTwos' => $categoryTwos,
            'categoryThrees' => $categoryThrees,
            'states' => $states,
            'cities' => $cities,
            'areas' => $areas,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'offer_type' => 'required|in:product_category,store_category,sales_volume,location',
            'offer_title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'offer_percentage' => 'required|numeric|min:0|max:100',
            'p_category_id' => 'nullable|exists:product_categories,id',
            'category_one_id' => 'nullable|exists:category_one,id',
            'category_two_id' => 'nullable|exists:category_two,id',
            'category_three_id' => 'nullable|exists:category_three,id',
            'store_ids' => 'nullable|array',
            'store_ids.*' => 'exists:stores,id',
            'min_sales_amount' => 'nullable|numeric|min:0',
            'max_sales_amount' => 'nullable|numeric|min:0',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $offer = Offer::findOrFail($id);
            $offer->update($request->all());

            return redirect()->route('offer-master.index')
                ->with('success', 'Offer updated successfully');
        } catch (\Throwable $e) {
            Log::error('Offer update failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update offer')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $offer = Offer::findOrFail($id);
            $offer->delete();

            return redirect()->back()->with('success', 'Offer deleted successfully');
        } catch (\Throwable $e) {
            Log::error('Offer deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete offer');
        }
    }

    public function toggleStatus($id)
    {
        try {
            $offer = Offer::findOrFail($id);
            $offer->is_active = !$offer->is_active;
            $offer->save();

            return redirect()->back()->with('success', 'Offer status updated successfully');
        } catch (\Throwable $e) {
            Log::error('Offer toggle failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update offer status');
        }
    }

    public function getCitiesByState($stateId)
    {
        $cities = City::where('state_id', $stateId)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($cities);
    }

    public function getAreasByCity($cityId)
    {
        $areas = Area::where('city_id', $cityId)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($areas);
    }

    public function getStoresByCategories(Request $request)
    {
        $query = Store::where('is_active', true);

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

        $stores = $query->select('id', 'name')->orderBy('name')->get();

        return response()->json($stores);
    }

    public function downloadTemplate()
    {
        try {
            $productCategories = ProductCategory::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
            $categoryOnes = CategoryOne::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
            $categoryTwos = CategoryTwo::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
            $categoryThrees = CategoryThree::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
            $states = State::where('is_active', true)->orderBy('name')->pluck('name')->toArray();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Offers');

            // Headers
            $headers = [
                'Offer Type',
                'Offer Title',
                'Description',
                'Offer %',
                'Product Category',
                'Store Cat 1',
                'Store Cat 2',
                'Store Cat 3',
                'Min Sales Amount',
                'Max Sales Amount',
                'State',
                'City',
                'Area',
                'Start Date (YYYY-MM-DD)',
                'End Date (YYYY-MM-DD)'
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
            $sheet->getStyle('A1:O1')->applyFromArray($headerStyle);

            // Dropdowns
            $offerTypeList = '"product_category,store_category,sales_volume,location"';
            $pCatList = '"' . implode(',', $productCategories) . '"';
            $cat1List = '"' . implode(',', $categoryOnes) . '"';
            $cat2List = '"' . implode(',', $categoryTwos) . '"';
            $cat3List = '"' . implode(',', $categoryThrees) . '"';
            $stateList = '"' . implode(',', $states) . '"';

            for ($row = 2; $row <= 1000; $row++) {
                // Offer Type dropdown
                $validation = $sheet->getCell('A' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                $validation->setFormula1($offerTypeList);

                // Product Category dropdown
                $validation = $sheet->getCell('E' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1($pCatList);

                // Store Categories dropdowns
                $validation = $sheet->getCell('F' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1($cat1List);

                $validation = $sheet->getCell('G' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1($cat2List);

                $validation = $sheet->getCell('H' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1($cat3List);

                // State dropdown
                $validation = $sheet->getCell('K' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1($stateList);
            }

            foreach (range('A', 'O') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $filename = 'offer_template_' . now()->format('Ymd_His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename={$filename}");
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;
        } catch (\Throwable $e) {
            Log::error('Offer template download failed: ' . $e->getMessage());
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
                $offerType = trim($row['A'] ?? '');
                $offerTitle = trim($row['B'] ?? '');
                $description = trim($row['C'] ?? '');
                $offerPercentage = trim($row['D'] ?? '');
                $pCatName = trim($row['E'] ?? '');
                $cat1Name = trim($row['F'] ?? '');
                $cat2Name = trim($row['G'] ?? '');
                $cat3Name = trim($row['H'] ?? '');
                $minSalesAmount = trim($row['I'] ?? '');
                $maxSalesAmount = trim($row['J'] ?? '');
                $stateName = trim($row['K'] ?? '');
                $cityName = trim($row['L'] ?? '');
                $areaName = trim($row['M'] ?? '');
                $startDate = trim($row['N'] ?? '');
                $endDate = trim($row['O'] ?? '');

                if (!$offerType || !$offerTitle || !$offerPercentage || !$startDate || !$endDate) {
                    $errors[] = "Row " . ($index + 2) . ": Offer type, title, percentage, and dates are required";
                    continue;
                }

                $data = [
                    'offer_type' => $offerType,
                    'offer_title' => $offerTitle,
                    'description' => $description,
                    'offer_percentage' => $offerPercentage,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'is_active' => true,
                ];

                // Find Product Category
                if ($pCatName) {
                    $pCat = ProductCategory::whereRaw('LOWER(name) = ?', [strtolower($pCatName)])->first();
                    $data['p_category_id'] = $pCat?->id;
                }

                // Find Store Categories
                if ($cat1Name) {
                    $cat1 = CategoryOne::whereRaw('LOWER(name) = ?', [strtolower($cat1Name)])->first();
                    $data['category_one_id'] = $cat1?->id;
                }
                if ($cat2Name) {
                    $cat2 = CategoryTwo::whereRaw('LOWER(name) = ?', [strtolower($cat2Name)])->first();
                    $data['category_two_id'] = $cat2?->id;
                }
                if ($cat3Name) {
                    $cat3 = CategoryThree::whereRaw('LOWER(name) = ?', [strtolower($cat3Name)])->first();
                    $data['category_three_id'] = $cat3?->id;
                }

                // Sales amounts
                if ($minSalesAmount) {
                    $data['min_sales_amount'] = $minSalesAmount;
                }
                if ($maxSalesAmount) {
                    $data['max_sales_amount'] = $maxSalesAmount;
                }

                // Find Location
                if ($stateName) {
                    $state = State::whereRaw('LOWER(name) = ?', [strtolower($stateName)])->first();
                    $data['state_id'] = $state?->id;
                }
                if ($cityName) {
                    $city = City::whereRaw('LOWER(name) = ?', [strtolower($cityName)])->first();
                    $data['city_id'] = $city?->id;
                }
                if ($areaName) {
                    $area = Area::whereRaw('LOWER(name) = ?', [strtolower($areaName)])->first();
                    $data['area_id'] = $area?->id;
                }

                Offer::create($data);
                $imported++;
            }

            $message = "{$imported} offers imported successfully";
            if (count($errors) > 0) {
                $message .= ". " . count($errors) . " rows skipped.";
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Offer upload failed: ' . $e->getMessage());
            return back()->with('error', 'Excel upload failed: ' . $e->getMessage());
        }
    }
}
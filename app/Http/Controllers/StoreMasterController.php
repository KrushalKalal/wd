<?php

namespace App\Http\Controllers;

use App\Helpers\RoleAccessHelper;
use App\Models\Area;
use App\Models\CategoryOne;
use App\Models\CategoryThree;
use App\Models\CategoryTwo;
use App\Models\City;
use App\Models\State;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class StoreMasterController extends Controller
{
    public function index(Request $request)
    {
        $query = Store::with(['state.zone', 'city', 'area', 'categoryOne', 'categoryTwo', 'categoryThree']);

        $query = RoleAccessHelper::applyRoleFilter($query);

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('contact_number_1', 'like', '%' . $request->search . '%')
                    ->orWhere('store_incharge', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('state_id') && $request->state_id) {
            $query->where('state_id', $request->state_id);
        }

        if ($request->has('city_id') && $request->city_id) {
            $query->where('city_id', $request->city_id);
        }

        if ($request->has('area_id') && $request->area_id) {
            $query->where('area_id', $request->area_id);
        }

        if ($request->has('category_one_id') && $request->category_one_id) {
            $query->where('category_one_id', $request->category_one_id);
        }

        if ($request->has('category_two_id') && $request->category_two_id) {
            $query->where('category_two_id', $request->category_two_id);
        }

        if ($request->has('category_three_id') && $request->category_three_id) {
            $query->where('category_three_id', $request->category_three_id);
        }

        $perPage = $request->get('per_page', 10);
        $stores = $query->orderBy('name')->paginate($perPage);

        $stateIds = RoleAccessHelper::getAccessibleStateIds();
        $states = State::whereIn('id', $stateIds)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $categoryOnes = CategoryOne::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryTwos = CategoryTwo::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryThrees = CategoryThree::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('StoreMaster/Index', [
            'records' => $stores,
            'states' => $states,
            'categoryOnes' => $categoryOnes,
            'categoryTwos' => $categoryTwos,
            'categoryThrees' => $categoryThrees,
            'filters' => [
                'search' => $request->search,
                'state_id' => $request->state_id,
                'city_id' => $request->city_id,
                'area_id' => $request->area_id,
                'category_one_id' => $request->category_one_id,
                'category_two_id' => $request->category_two_id,
                'category_three_id' => $request->category_three_id,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create()
    {
        $userLocation = RoleAccessHelper::getUserLocation();
        $locationLocks = RoleAccessHelper::getLocationLocks();

        $categoryOnes = CategoryOne::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryTwos = CategoryTwo::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryThrees = CategoryThree::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        $areas = [];
        if ($userLocation['city_id']) {
            $areas = Area::where('city_id', $userLocation['city_id'])
                ->where('is_active', true)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        }

        return Inertia::render('StoreMaster/Form', [
            'userLocation' => $userLocation,
            'locationLocks' => $locationLocks,
            'categoryOnes' => $categoryOnes,
            'categoryTwos' => $categoryTwos,
            'categoryThrees' => $categoryThrees,
            'areas' => $areas,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'store_legal_name' => 'nullable|string|max:255',
            'store_incharge' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'area_id' => 'required|exists:areas,id',
            'pin_code' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'category_one_id' => 'nullable|exists:category_one,id',
            'category_two_id' => 'nullable|exists:category_two,id',
            'category_three_id' => 'nullable|exists:category_three,id',
            'contact_number_1' => 'nullable|string|max:20',
            'contact_number_2' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        try {
            $accessCheck = RoleAccessHelper::validateLocationAccess(
                $request->state_id,
                $request->city_id
            );

            if (!$accessCheck['valid']) {
                return redirect()->back()
                    ->with('error', $accessCheck['message'])
                    ->withInput();
            }

            $locationLocks = RoleAccessHelper::getLocationLocks();
            $userLocation = RoleAccessHelper::getUserLocation();

            $data = $request->all();
            $data['country'] = 'India';
            $data['is_active'] = true;
            $data['manual_stock_entry'] = true;

            if ($locationLocks['zone_id'])
                $data['zone_id'] = $userLocation['zone_id'];
            if ($locationLocks['state_id'])
                $data['state_id'] = $userLocation['state_id'];
            if ($locationLocks['city_id'])
                $data['city_id'] = $userLocation['city_id'];

            // Billing details as JSON
            if ($request->billing_address) {
                $data['billing_details'] = [
                    'address' => $request->billing_address,
                    'latitude' => $request->billing_latitude,
                    'longitude' => $request->billing_longitude,
                ];
            }

            // Shipping details as JSON
            if ($request->shipping_address) {
                $data['shipping_details'] = [
                    'address' => $request->shipping_address,
                    'latitude' => $request->shipping_latitude,
                    'longitude' => $request->shipping_longitude,
                ];
            }

            Store::create($data);

            return redirect()->route('store-master.index')
                ->with('success', 'Store added successfully');
        } catch (\Throwable $e) {
            Log::error('Store creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to add store')
                ->withInput();
        }
    }

    public function edit($id)
    {
        $store = Store::with(['state', 'city', 'area', 'categoryOne', 'categoryTwo', 'categoryThree'])
            ->findOrFail($id);

        $userLocation = RoleAccessHelper::getUserLocation();
        $locationLocks = RoleAccessHelper::getLocationLocks();

        $categoryOnes = CategoryOne::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryTwos = CategoryTwo::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryThrees = CategoryThree::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        $areas = [];
        if ($store->city_id) {
            $areas = Area::where('city_id', $store->city_id)
                ->where('is_active', true)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        }

        return Inertia::render('StoreMaster/Form', [
            'store' => $store,
            'userLocation' => $userLocation,
            'locationLocks' => $locationLocks,
            'categoryOnes' => $categoryOnes,
            'categoryTwos' => $categoryTwos,
            'categoryThrees' => $categoryThrees,
            'areas' => $areas,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'store_legal_name' => 'nullable|string|max:255',
            'store_incharge' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'area_id' => 'required|exists:areas,id',
            'pin_code' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'category_one_id' => 'nullable|exists:category_one,id',
            'category_two_id' => 'nullable|exists:category_two,id',
            'category_three_id' => 'nullable|exists:category_three,id',
            'contact_number_1' => 'nullable|string|max:20',
            'contact_number_2' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        try {
            $accessCheck = RoleAccessHelper::validateLocationAccess(
                $request->state_id,
                $request->city_id
            );

            if (!$accessCheck['valid']) {
                return redirect()->back()
                    ->with('error', $accessCheck['message'])
                    ->withInput();
            }

            $store = Store::findOrFail($id);
            $data = $request->all();

            $data['country'] = 'India';
            $data['manual_stock_entry'] = true;

            if ($request->billing_address) {
                $data['billing_details'] = [
                    'address' => $request->billing_address,
                    'latitude' => $request->billing_latitude,
                    'longitude' => $request->billing_longitude,
                ];
            }

            if ($request->shipping_address) {
                $data['shipping_details'] = [
                    'address' => $request->shipping_address,
                    'latitude' => $request->shipping_latitude,
                    'longitude' => $request->shipping_longitude,
                ];
            }

            $store->update($data);

            return redirect()->route('store-master.index')
                ->with('success', 'Store updated successfully');
        } catch (\Throwable $e) {
            Log::error('Store update failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update store')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            Store::findOrFail($id)->delete();
            return redirect()->back()->with('success', 'Store deleted successfully');
        } catch (\Throwable $e) {
            Log::error('Store deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete store');
        }
    }

    public function toggleStatus($id)
    {
        try {
            $store = Store::findOrFail($id);
            $store->is_active = !$store->is_active;
            $store->save();
            return redirect()->back()->with('success', 'Store status updated successfully');
        } catch (\Throwable $e) {
            Log::error('Store toggle failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update store status');
        }
    }

    public function downloadTemplate()
    {
        try {
            $stateIds = RoleAccessHelper::getAccessibleStateIds();
            $states = State::whereIn('id', $stateIds)
                ->where('is_active', true)
                ->with('cities.areas')
                ->orderBy('name')
                ->get();

            $categoryOnes = CategoryOne::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
            $categoryTwos = CategoryTwo::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
            $categoryThrees = CategoryThree::where('is_active', true)->orderBy('name')->pluck('name')->toArray();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Stores');

            $headers = [
                'Store Name',
                'Legal Name',
                'Store Incharge',
                'State',
                'City',
                'Area',
                'Pin Code',
                'Category One',
                'Category Two',
                'Category Three',
                'Contact Number 1',
                'Contact Number 2',
                'Email'
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
            $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);

            $dataSheet = $spreadsheet->createSheet();
            $dataSheet->setTitle('Data');

            $stateNames = $states->pluck('name')->toArray();
            $stateList = '"' . implode(',', $stateNames) . '"';
            $cat1List = '"' . implode(',', $categoryOnes) . '"';
            $cat2List = '"' . implode(',', $categoryTwos) . '"';
            $cat3List = '"' . implode(',', $categoryThrees) . '"';

            $dataCol = 1;
            foreach ($states as $state) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($dataCol);
                $stateCities = $state->cities;
                if ($stateCities->count() > 0) {
                    $dataSheet->setCellValue($columnLetter . '1', $state->name);
                    $cityRow = 2;
                    foreach ($stateCities as $city) {
                        $dataSheet->setCellValue($columnLetter . $cityRow, $city->name);
                        $cityRow++;
                    }
                    $rangeName = 'Cities_' . preg_replace('/[^A-Za-z0-9]/', '_', $state->name);
                    $range = $columnLetter . '2:' . $columnLetter . ($cityRow - 1);
                    $spreadsheet->addNamedRange(
                        new \PhpOffice\PhpSpreadsheet\NamedRange($rangeName, $dataSheet, $range)
                    );
                }
                $dataCol++;
            }

            $areaCol = $dataCol;
            foreach ($states as $state) {
                foreach ($state->cities as $city) {
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($areaCol);
                    if ($city->areas->count() > 0) {
                        $dataSheet->setCellValue($columnLetter . '1', $state->name . '_' . $city->name);
                        $areaRow = 2;
                        foreach ($city->areas as $area) {
                            $dataSheet->setCellValue($columnLetter . $areaRow, $area->name);
                            $areaRow++;
                        }
                        $rangeName = 'Areas_' . preg_replace('/[^A-Za-z0-9]/', '_', $state->name . '_' . $city->name);
                        $range = $columnLetter . '2:' . $columnLetter . ($areaRow - 1);
                        $spreadsheet->addNamedRange(
                            new \PhpOffice\PhpSpreadsheet\NamedRange($rangeName, $dataSheet, $range)
                        );
                    }
                    $areaCol++;
                }
            }

            $dataSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

            for ($row = 2; $row <= 1000; $row++) {
                // State dropdown Column D
                $validation = $sheet->getCell('D' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                $validation->setFormula1($stateList);

                // City dropdown Column E
                $validation = $sheet->getCell('E' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                $validation->setFormula1('INDIRECT("Cities_"&SUBSTITUTE(D' . $row . '," ","_"))');

                // Area dropdown Column F
                $validation = $sheet->getCell('F' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                $validation->setFormula1('INDIRECT("Areas_"&SUBSTITUTE(D' . $row . '," ","_")&"_"&SUBSTITUTE(E' . $row . '," ","_"))');

                // Category One Column H
                if (!empty($categoryOnes)) {
                    $validation = $sheet->getCell('H' . $row)->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1($cat1List);
                }

                // Category Two Column I
                if (!empty($categoryTwos)) {
                    $validation = $sheet->getCell('I' . $row)->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1($cat2List);
                }

                // Category Three Column J
                if (!empty($categoryThrees)) {
                    $validation = $sheet->getCell('J' . $row)->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1($cat3List);
                }
            }

            foreach (range('A', 'M') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $spreadsheet->setActiveSheetIndex(0);

            $writer = new Xlsx($spreadsheet);
            $filename = 'store_template_' . now()->format('Ymd_His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename={$filename}");
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;
        } catch (\Throwable $e) {
            Log::error('Store template download failed: ' . $e->getMessage());
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
                $legalName = trim($row['B'] ?? '');
                $storeIncharge = trim($row['C'] ?? '');
                $stateName = trim($row['D'] ?? '');
                $cityName = trim($row['E'] ?? '');
                $areaName = trim($row['F'] ?? '');
                $pinCode = trim($row['G'] ?? '');
                $cat1Name = trim($row['H'] ?? '');
                $cat2Name = trim($row['I'] ?? '');
                $cat3Name = trim($row['J'] ?? '');
                $contact1 = trim($row['K'] ?? '');
                $contact2 = trim($row['L'] ?? '');
                $email = trim($row['M'] ?? '');

                if (!$storeName || !$stateName || !$cityName || !$areaName) {
                    $errors[] = "Row " . ($index + 2) . ": Store name, state, city, and area are required";
                    continue;
                }

                $state = State::whereRaw('LOWER(name) = ?', [strtolower($stateName)])->first();
                if (!$state) {
                    $errors[] = "Row " . ($index + 2) . ": State '{$stateName}' not found";
                    continue;
                }

                $city = City::where('state_id', $state->id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($cityName)])
                    ->first();
                if (!$city) {
                    $errors[] = "Row " . ($index + 2) . ": City '{$cityName}' not found";
                    continue;
                }

                $area = Area::where('city_id', $city->id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($areaName)])
                    ->first();
                if (!$area) {
                    $errors[] = "Row " . ($index + 2) . ": Area '{$areaName}' not found";
                    continue;
                }

                $cat1Id = null;
                $cat2Id = null;
                $cat3Id = null;

                if ($cat1Name) {
                    $cat1 = CategoryOne::whereRaw('LOWER(name) = ?', [strtolower($cat1Name)])->first();
                    $cat1Id = $cat1?->id;
                }
                if ($cat2Name) {
                    $cat2 = CategoryTwo::whereRaw('LOWER(name) = ?', [strtolower($cat2Name)])->first();
                    $cat2Id = $cat2?->id;
                }
                if ($cat3Name) {
                    $cat3 = CategoryThree::whereRaw('LOWER(name) = ?', [strtolower($cat3Name)])->first();
                    $cat3Id = $cat3?->id;
                }

                Store::create([
                    'name' => $storeName,
                    'store_legal_name' => $legalName,
                    'store_incharge' => $storeIncharge,
                    'state_id' => $state->id,
                    'city_id' => $city->id,
                    'area_id' => $area->id,
                    'pin_code' => $pinCode,
                    'category_one_id' => $cat1Id,
                    'category_two_id' => $cat2Id,
                    'category_three_id' => $cat3Id,
                    'contact_number_1' => $contact1,
                    'contact_number_2' => $contact2,
                    'email' => $email,
                    'country' => 'India',
                    'manual_stock_entry' => true,
                    'is_active' => true,
                ]);

                $imported++;
            }

            $message = "{$imported} stores imported successfully";
            if (count($errors) > 0) {
                $message .= ". " . count($errors) . " rows skipped.";
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Store upload failed: ' . $e->getMessage());
            return back()->with('error', 'Excel upload failed: ' . $e->getMessage());
        }
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

    public function getAllActiveStores()
    {
        $query = Store::query()
            ->select(['id', 'name', 'state_id', 'city_id', 'area_id', 'pin_code'])
            ->with(['state:id,name', 'city:id,name', 'area:id,name'])
            ->where('is_active', true);

        $query = RoleAccessHelper::applyRoleFilter($query);

        return response()->json($query->orderBy('name')->get());
    }
}
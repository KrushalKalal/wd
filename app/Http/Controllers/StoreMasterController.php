<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\State;
use App\Models\City;
use App\Models\Area;
use App\Models\CategoryOne;
use App\Models\CategoryTwo;
use App\Models\CategoryThree;
use App\Helpers\RoleAccessHelper;
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

        // Apply role-based filter
        $query = RoleAccessHelper::applyRoleFilter($query);

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('contact_number_1', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by state
        if ($request->has('state_id') && $request->state_id) {
            $query->where('state_id', $request->state_id);
        }

        // Filter by city
        if ($request->has('city_id') && $request->city_id) {
            $query->where('city_id', $request->city_id);
        }

        // Filter by area
        if ($request->has('area_id') && $request->area_id) {
            $query->where('area_id', $request->area_id);
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

        $perPage = $request->get('per_page', 10);
        $stores = $query->orderBy('name')->paginate($perPage);

        // Get accessible states for filter
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
        $stateIds = RoleAccessHelper::getAccessibleStateIds();
        $states = State::whereIn('id', $stateIds)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $categoryOnes = CategoryOne::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryTwos = CategoryTwo::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryThrees = CategoryThree::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('StoreMaster/Form', [
            'states' => $states,
            'categoryOnes' => $categoryOnes,
            'categoryTwos' => $categoryTwos,
            'categoryThrees' => $categoryThrees,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'area_id' => 'required|exists:areas,id',
            'pin_code' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'category_one_id' => 'nullable|exists:category_one,id',
            'category_two_id' => 'nullable|exists:category_two,id',
            'category_three_id' => 'nullable|exists:category_three,id',
            'contact_number_1' => 'nullable|string|max:20',
            'contact_number_2' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'billing_details' => 'nullable|json',
            'shipping_details' => 'nullable|json',
            'manual_stock_entry' => 'nullable|boolean',
        ]);

        try {
            $data = $request->all();
            $data['is_active'] = true;

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

        $stateIds = RoleAccessHelper::getAccessibleStateIds();
        $states = State::whereIn('id', $stateIds)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $categoryOnes = CategoryOne::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryTwos = CategoryTwo::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $categoryThrees = CategoryThree::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('StoreMaster/Form', [
            'store' => $store,
            'states' => $states,
            'categoryOnes' => $categoryOnes,
            'categoryTwos' => $categoryTwos,
            'categoryThrees' => $categoryThrees,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'area_id' => 'required|exists:areas,id',
            'pin_code' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'category_one_id' => 'nullable|exists:category_one,id',
            'category_two_id' => 'nullable|exists:category_two,id',
            'category_three_id' => 'nullable|exists:category_three,id',
            'contact_number_1' => 'nullable|string|max:20',
            'contact_number_2' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'billing_details' => 'nullable|json',
            'shipping_details' => 'nullable|json',
            'manual_stock_entry' => 'nullable|boolean',
        ]);

        try {
            $store = Store::findOrFail($id);
            $store->update($request->all());

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
            $store = Store::findOrFail($id);
            $store->delete();

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

            // Headers
            $headers = [
                'Store Name',
                'State',
                'City',
                'Area',
                'Pin Code',
                'Latitude',
                'Longitude',
                'Category One',
                'Category Two',
                'Category Three',
                'Contact Number 1',
                'Contact Number 2',
                'Email',
                'Manual Stock Entry (Yes/No)'
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
            $sheet->getStyle('A1:N1')->applyFromArray($headerStyle);

            // Create hidden data sheet
            $dataSheet = $spreadsheet->createSheet();
            $dataSheet->setTitle('Data');

            // States dropdown
            $stateNames = $states->pluck('name')->toArray();
            $stateList = '"' . implode(',', $stateNames) . '"';

            // Category dropdowns
            $cat1List = '"' . implode(',', $categoryOnes) . '"';
            $cat2List = '"' . implode(',', $categoryTwos) . '"';
            $cat3List = '"' . implode(',', $categoryThrees) . '"';

            // Cities with named ranges
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

            // Areas with named ranges
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

            // Add dropdowns to main sheet
            for ($row = 2; $row <= 1000; $row++) {
                // State dropdown (Column B)
                $validation = $sheet->getCell('B' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                $validation->setFormula1($stateList);

                // City dropdown (Column C) - dynamic
                $validation = $sheet->getCell('C' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                $validation->setFormula1('INDIRECT("Cities_"&SUBSTITUTE(B' . $row . '," ","_"))');

                // Area dropdown (Column D) - dynamic
                $validation = $sheet->getCell('D' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                $validation->setFormula1('INDIRECT("Areas_"&SUBSTITUTE(B' . $row . '," ","_")&"_"&SUBSTITUTE(C' . $row . '," ","_"))');

                // Category One dropdown (Column H)
                $validation = $sheet->getCell('H' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1($cat1List);

                // Category Two dropdown (Column I)
                $validation = $sheet->getCell('I' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1($cat2List);

                // Category Three dropdown (Column J)
                $validation = $sheet->getCell('J' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1($cat3List);

                // Manual Stock Entry dropdown (Column N)
                $validation = $sheet->getCell('N' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"Yes,No"');

                // Default value
                $sheet->setCellValue('N' . $row, 'Yes');
            }

            // Auto-size columns
            foreach (range('A', 'N') as $col) {
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
                $stateName = trim($row['B'] ?? '');
                $cityName = trim($row['C'] ?? '');
                $areaName = trim($row['D'] ?? '');
                $pinCode = trim($row['E'] ?? '');
                $latitude = trim($row['F'] ?? '');
                $longitude = trim($row['G'] ?? '');
                $cat1Name = trim($row['H'] ?? '');
                $cat2Name = trim($row['I'] ?? '');
                $cat3Name = trim($row['J'] ?? '');
                $contact1 = trim($row['K'] ?? '');
                $contact2 = trim($row['L'] ?? '');
                $email = trim($row['M'] ?? '');
                $manualStock = trim($row['N'] ?? 'Yes');

                if (!$storeName || !$stateName || !$cityName || !$areaName) {
                    $errors[] = "Row " . ($index + 2) . ": Store name, state, city, and area are required";
                    continue;
                }

                // Find state
                $state = State::whereRaw('LOWER(name) = ?', [strtolower($stateName)])->first();
                if (!$state) {
                    $errors[] = "Row " . ($index + 2) . ": State '{$stateName}' not found";
                    continue;
                }

                // Find city
                $city = City::where('state_id', $state->id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($cityName)])
                    ->first();
                if (!$city) {
                    $errors[] = "Row " . ($index + 2) . ": City '{$cityName}' not found";
                    continue;
                }

                // Find area
                $area = Area::where('city_id', $city->id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($areaName)])
                    ->first();
                if (!$area) {
                    $errors[] = "Row " . ($index + 2) . ": Area '{$areaName}' not found";
                    continue;
                }

                // Find categories (optional)
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
                    'state_id' => $state->id,
                    'city_id' => $city->id,
                    'area_id' => $area->id,
                    'pin_code' => $pinCode,
                    'latitude' => $latitude ?: null,
                    'longitude' => $longitude ?: null,
                    'category_one_id' => $cat1Id,
                    'category_two_id' => $cat2Id,
                    'category_three_id' => $cat3Id,
                    'contact_number_1' => $contact1,
                    'contact_number_2' => $contact2,
                    'email' => $email,
                    'manual_stock_entry' => strtolower($manualStock) === 'yes' ? true : false,
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

    // API endpoint to get areas by city
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
            ->select([
                'id',
                'name',
                'state_id',
                'city_id',
                'area_id',
                'pin_code',
            ])
            ->with([
                'state:id,name',
                'city:id,name',
                'area:id,name',
            ])
            ->where('is_active', true);

        // Apply role-based filter
        $query = RoleAccessHelper::applyRoleFilter($query);

        $stores = $query->orderBy('name')->get();

        return response()->json($stores);
    }
}
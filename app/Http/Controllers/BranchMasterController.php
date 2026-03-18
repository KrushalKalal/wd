<?php

namespace App\Http\Controllers;

use App\Helpers\RoleAccessHelper;
use App\Models\Area;
use App\Models\Branch;
use App\Models\City;
use App\Models\Company;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class BranchMasterController extends Controller
{
    public function index(Request $request)
    {
        $query = Branch::with(['company', 'state.zone', 'city', 'area']);

        $query = RoleAccessHelper::applyRoleFilter($query);

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhereHas('company', function ($cq) use ($request) {
                        $cq->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        if ($request->has('company_id') && $request->company_id) {
            $query->where('company_id', $request->company_id);
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

        $perPage = $request->get('per_page', 10);
        $branches = $query->orderBy('name')->paginate($perPage);

        // Single company
        $company = Company::where('is_active', true)->first();

        $stateIds = RoleAccessHelper::getAccessibleStateIds();
        $states = State::whereIn('id', $stateIds)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('BranchMaster/Index', [
            'records' => $branches,
            'company' => $company,
            'states' => $states,
            'filters' => [
                'search' => $request->search,
                'company_id' => $request->company_id,
                'state_id' => $request->state_id,
                'city_id' => $request->city_id,
                'area_id' => $request->area_id,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create()
    {
        // Single company pre-filled
        $company = Company::where('is_active', true)->first();

        // User location for pre-filling and locking
        $userLocation = RoleAccessHelper::getUserLocation();
        $locationLocks = RoleAccessHelper::getLocationLocks();

        // Pre-load areas if city is locked
        $areas = [];
        if ($userLocation['city_id']) {
            $areas = Area::where('city_id', $userLocation['city_id'])
                ->where('is_active', true)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        }

        return Inertia::render('BranchMaster/Form', [
            'company' => $company,
            'userLocation' => $userLocation,
            'locationLocks' => $locationLocks,
            'areas' => $areas,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'pin_code' => 'nullable|string|max:10',
            'contact_number_1' => 'nullable|string|max:20',
            'contact_number_2' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        try {
            // Validate location access
            $accessCheck = RoleAccessHelper::validateLocationAccess(
                $request->state_id,
                $request->city_id
            );

            if (!$accessCheck['valid']) {
                return redirect()->back()
                    ->with('error', $accessCheck['message'])
                    ->withInput();
            }

            // Single company
            $company = Company::where('is_active', true)->firstOrFail();

            // Inject locked location from logged in user
            $locationLocks = RoleAccessHelper::getLocationLocks();
            $userLocation = RoleAccessHelper::getUserLocation();

            $data = $request->all();
            $data['company_id'] = $company->id;
            $data['country'] = 'India';
            $data['is_active'] = true;

            if ($locationLocks['zone_id'])
                $data['zone_id'] = $userLocation['zone_id'];
            if ($locationLocks['state_id'])
                $data['state_id'] = $userLocation['state_id'];
            if ($locationLocks['city_id'])
                $data['city_id'] = $userLocation['city_id'];

            Branch::create($data);

            return redirect()->route('branch-master.index')
                ->with('success', 'Branch added successfully');
        } catch (\Throwable $e) {
            Log::error('Branch creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to add branch')
                ->withInput();
        }
    }

    public function edit($id)
    {
        $branch = Branch::with(['company', 'state', 'city', 'area'])->findOrFail($id);

        $company = Company::where('is_active', true)->first();
        $userLocation = RoleAccessHelper::getUserLocation();
        $locationLocks = RoleAccessHelper::getLocationLocks();

        $areas = [];
        if ($branch->city_id) {
            $areas = Area::where('city_id', $branch->city_id)
                ->where('is_active', true)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        }

        return Inertia::render('BranchMaster/Form', [
            'branch' => $branch,
            'company' => $company,
            'userLocation' => $userLocation,
            'locationLocks' => $locationLocks,
            'areas' => $areas,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'pin_code' => 'nullable|string|max:10',
            'contact_number_1' => 'nullable|string|max:20',
            'contact_number_2' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
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

            $branch = Branch::findOrFail($id);
            $data = $request->all();
            $data['country'] = 'India';

            $locationLocks = RoleAccessHelper::getLocationLocks();
            $userLocation = RoleAccessHelper::getUserLocation();

            if ($locationLocks['zone_id'])
                $data['zone_id'] = $userLocation['zone_id'];
            if ($locationLocks['state_id'])
                $data['state_id'] = $userLocation['state_id'];
            if ($locationLocks['city_id'])
                $data['city_id'] = $userLocation['city_id'];

            $branch->update($data);

            return redirect()->route('branch-master.index')
                ->with('success', 'Branch updated successfully');
        } catch (\Throwable $e) {
            Log::error('Branch update failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update branch')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            Branch::findOrFail($id)->delete();
            return redirect()->back()->with('success', 'Branch deleted successfully');
        } catch (\Throwable $e) {
            Log::error('Branch deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete branch');
        }
    }

    public function toggleStatus($id)
    {
        try {
            $branch = Branch::findOrFail($id);
            $branch->is_active = !$branch->is_active;
            $branch->save();
            return redirect()->back()->with('success', 'Branch status updated successfully');
        } catch (\Throwable $e) {
            Log::error('Branch toggle failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update branch status');
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

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Branches');

            $headers = [
                'Branch Name',
                'Address',
                'State',
                'City',
                'Area',
                'Pin Code',
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
            $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

            $dataSheet = $spreadsheet->createSheet();
            $dataSheet->setTitle('Data');

            $stateNames = $states->pluck('name')->toArray();
            $stateList = '"' . implode(',', $stateNames) . '"';

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
                $validation = $sheet->getCell('C' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1($stateList);

                $validation = $sheet->getCell('D' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('INDIRECT("Cities_"&SUBSTITUTE(C' . $row . '," ","_"))');

                $validation = $sheet->getCell('E' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('INDIRECT("Areas_"&SUBSTITUTE(C' . $row . '," ","_")&"_"&SUBSTITUTE(D' . $row . '," ","_"))');
            }

            foreach (range('A', 'I') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $spreadsheet->setActiveSheetIndex(0);

            $writer = new Xlsx($spreadsheet);
            $filename = 'branch_template_' . now()->format('Ymd_His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename={$filename}");
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;
        } catch (\Throwable $e) {
            Log::error('Branch template download failed: ' . $e->getMessage());
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

            // Single company
            $company = Company::where('is_active', true)->first();

            foreach (array_slice($rows, 1) as $index => $row) {
                $branchName = trim($row['A'] ?? '');
                $address = trim($row['B'] ?? '');
                $stateName = trim($row['C'] ?? '');
                $cityName = trim($row['D'] ?? '');
                $areaName = trim($row['E'] ?? '');
                $pinCode = trim($row['F'] ?? '');
                $contact1 = trim($row['G'] ?? '');
                $contact2 = trim($row['H'] ?? '');
                $email = trim($row['I'] ?? '');

                if (!$branchName) {
                    $errors[] = "Row " . ($index + 2) . ": Branch name is required";
                    continue;
                }

                $stateId = null;
                $cityId = null;
                $areaId = null;

                if ($stateName) {
                    $state = State::whereRaw('LOWER(name) = ?', [strtolower($stateName)])->first();
                    if (!$state) {
                        $errors[] = "Row " . ($index + 2) . ": State '{$stateName}' not found";
                        continue;
                    }
                    $stateId = $state->id;

                    if ($cityName) {
                        $city = City::where('state_id', $stateId)
                            ->whereRaw('LOWER(name) = ?', [strtolower($cityName)])
                            ->first();
                        if (!$city) {
                            $errors[] = "Row " . ($index + 2) . ": City '{$cityName}' not found";
                            continue;
                        }
                        $cityId = $city->id;

                        if ($areaName) {
                            $area = Area::where('city_id', $cityId)
                                ->whereRaw('LOWER(name) = ?', [strtolower($areaName)])
                                ->first();
                            if (!$area) {
                                $errors[] = "Row " . ($index + 2) . ": Area '{$areaName}' not found";
                                continue;
                            }
                            $areaId = $area->id;
                        }
                    }
                }

                Branch::create([
                    'company_id' => $company->id,
                    'name' => $branchName,
                    'address' => $address,
                    'state_id' => $stateId,
                    'city_id' => $cityId,
                    'area_id' => $areaId,
                    'pin_code' => $pinCode,
                    'country' => 'India',
                    'contact_number_1' => $contact1,
                    'contact_number_2' => $contact2,
                    'email' => $email,
                    'is_active' => true,
                ]);

                $imported++;
            }

            $message = "{$imported} branches imported successfully";
            if (count($errors) > 0) {
                $message .= ". " . count($errors) . " rows skipped.";
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Branch upload failed: ' . $e->getMessage());
            return back()->with('error', 'Excel upload failed: ' . $e->getMessage());
        }
    }
}
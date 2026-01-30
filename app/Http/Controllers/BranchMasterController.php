<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\State;
use App\Models\City;
use App\Models\Area;
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
        $query = Branch::with(['company', 'state', 'city', 'area']);

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhereHas('company', function ($cq) use ($request) {
                        $cq->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        // Filter by company
        if ($request->has('company_id') && $request->company_id) {
            $query->where('company_id', $request->company_id);
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

        $perPage = $request->get('per_page', 10);
        $branches = $query->orderBy('name')->paginate($perPage);

        $companies = Company::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $states = State::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('BranchMaster/Index', [
            'records' => $branches,
            'companies' => $companies,
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
        $companies = Company::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $states = State::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('BranchMaster/Form', [
            'companies' => $companies,
            'states' => $states,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'pin_code' => 'nullable|string|max:10',
            'country' => 'required|string|max:255',
            'contact_number_1' => 'nullable|string|max:20',
            'contact_number_2' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        try {
            $data = $request->all();
            $data['is_active'] = true;
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
        $companies = Company::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $states = State::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('BranchMaster/Form', [
            'branch' => $branch,
            'companies' => $companies,
            'states' => $states,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'pin_code' => 'nullable|string|max:10',
            'country' => 'required|string|max:255',
            'contact_number_1' => 'nullable|string|max:20',
            'contact_number_2' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        try {
            $branch = Branch::findOrFail($id);
            $branch->update($request->all());

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
            $branch = Branch::findOrFail($id);
            $branch->delete();

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
            $companies = Company::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
            $states = State::where('is_active', true)->with('cities.areas')->orderBy('name')->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Branches');

            // Headers
            $headers = [
                'Company Name',
                'Branch Name',
                'Address',
                'State',
                'City',
                'Area',
                'Pin Code',
                'Country',
                'Contact Number 1',
                'Contact Number 2',
                'Email'
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
            $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

            // Create hidden data sheet
            $dataSheet = $spreadsheet->createSheet();
            $dataSheet->setTitle('Data');

            // Company dropdown
            $companyList = '"' . implode(',', $companies) . '"';

            // States
            $stateNames = $states->pluck('name')->toArray();
            $stateList = '"' . implode(',', $stateNames) . '"';

            // Cities and Areas (same as Company controller)
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

            // Areas
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

            // Add dropdowns
            for ($row = 2; $row <= 1000; $row++) {
                // Company dropdown (Column A)
                $validation = $sheet->getCell('A' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                $validation->setFormula1($companyList);

                // State dropdown (Column D)
                $validation = $sheet->getCell('D' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1($stateList);

                // City dropdown (Column E)
                $validation = $sheet->getCell('E' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('INDIRECT("Cities_"&SUBSTITUTE(D' . $row . '," ","_"))');

                // Area dropdown (Column F)
                $validation = $sheet->getCell('F' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('INDIRECT("Areas_"&SUBSTITUTE(D' . $row . '," ","_")&"_"&SUBSTITUTE(E' . $row . '," ","_"))');

                // Default country
                $sheet->setCellValue('H' . $row, 'India');
            }

            foreach (range('A', 'K') as $col) {
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

            foreach (array_slice($rows, 1) as $index => $row) {
                $companyName = trim($row['A'] ?? '');
                $branchName = trim($row['B'] ?? '');
                $address = trim($row['C'] ?? '');
                $stateName = trim($row['D'] ?? '');
                $cityName = trim($row['E'] ?? '');
                $areaName = trim($row['F'] ?? '');
                $pinCode = trim($row['G'] ?? '');
                $country = trim($row['H'] ?? 'India');
                $contact1 = trim($row['I'] ?? '');
                $contact2 = trim($row['J'] ?? '');
                $email = trim($row['K'] ?? '');

                if (!$companyName || !$branchName) {
                    $errors[] = "Row " . ($index + 2) . ": Company and branch names are required";
                    continue;
                }

                // Find company
                $company = Company::whereRaw('LOWER(name) = ?', [strtolower($companyName)])->first();
                if (!$company) {
                    $errors[] = "Row " . ($index + 2) . ": Company '{$companyName}' not found";
                    continue;
                }

                $stateId = null;
                $cityId = null;
                $areaId = null;

                // Find location details (same as Company upload)
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
                    'country' => $country,
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
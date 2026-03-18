<?php

namespace App\Http\Controllers;

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

class CompanyMasterController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::with(['state', 'city', 'area']);

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email_1', 'like', '%' . $request->search . '%')
                    ->orWhere('contact_number_1', 'like', '%' . $request->search . '%');
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

        $perPage = $request->get('per_page', 10);
        $companies = $query->orderBy('name')->paginate($perPage);

        $states = State::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('CompanyMaster/Index', [
            'records' => $companies,
            'states' => $states,
            'filters' => [
                'search' => $request->search,
                'state_id' => $request->state_id,
                'city_id' => $request->city_id,
                'area_id' => $request->area_id,
                'per_page' => $perPage,
            ],
        ]);
    }

    // NO create() — single company, edit only

    public function edit($id)
    {
        $company = Company::with(['state', 'city', 'area'])->findOrFail($id);

        // Load areas for current city
        $areas = [];
        if ($company->city_id) {
            $areas = Area::where('city_id', $company->city_id)
                ->where('is_active', true)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        }

        return Inertia::render('CompanyMaster/Form', [
            'company' => $company,
            'areas' => $areas,
        ]);
    }

    // NO store() — single company, edit only

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:companies,name,' . $id,
            'address' => 'nullable|string',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'pin_code' => 'nullable|string|max:10',
            'contact_number_1' => 'nullable|string|max:20',
            'email_1' => 'nullable|email|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        try {
            $company = Company::findOrFail($id);

            $company->update([
                'name' => $request->name,
                'address' => $request->address,
                'state_id' => $request->state_id,
                'city_id' => $request->city_id,
                'area_id' => $request->area_id,
                'pin_code' => $request->pin_code,
                'contact_number_1' => $request->contact_number_1,
                'email_1' => $request->email_1,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'country' => 'India',
            ]);

            return redirect()->route('company-master.index')
                ->with('success', 'Company updated successfully');
        } catch (\Throwable $e) {
            Log::error('Company update failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update company')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $company = Company::findOrFail($id);
            $company->delete();
            return redirect()->back()->with('success', 'Company deleted successfully');
        } catch (\Throwable $e) {
            Log::error('Company deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete company. It may have associated branches.');
        }
    }

    public function toggleStatus($id)
    {
        try {
            $company = Company::findOrFail($id);
            $company->is_active = !$company->is_active;
            $company->save();
            return redirect()->back()->with('success', 'Company status updated successfully');
        } catch (\Throwable $e) {
            Log::error('Company toggle failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update company status');
        }
    }

    public function downloadTemplate()
    {
        try {
            $states = State::where('is_active', true)->with('cities.areas')->orderBy('name')->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Companies');

            $headers = [
                'Company Name',
                'Address',
                'State',
                'City',
                'Area',
                'Pin Code',
                'Contact Number 1',
                'Email 1'
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
            $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

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

            foreach (range('A', 'H') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $spreadsheet->setActiveSheetIndex(0);

            $writer = new Xlsx($spreadsheet);
            $filename = 'company_template_' . now()->format('Ymd_His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename={$filename}");
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;
        } catch (\Throwable $e) {
            Log::error('Company template download failed: ' . $e->getMessage());
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
                $address = trim($row['B'] ?? '');
                $stateName = trim($row['C'] ?? '');
                $cityName = trim($row['D'] ?? '');
                $areaName = trim($row['E'] ?? '');
                $pinCode = trim($row['F'] ?? '');
                $contact1 = trim($row['G'] ?? '');
                $email1 = trim($row['H'] ?? '');

                if (!$companyName) {
                    $errors[] = "Row " . ($index + 2) . ": Company name is required";
                    continue;
                }

                $exists = Company::whereRaw('LOWER(name) = ?', [strtolower($companyName)])->first();
                if ($exists) {
                    $errors[] = "Row " . ($index + 2) . ": Company '{$companyName}' already exists";
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
                            $errors[] = "Row " . ($index + 2) . ": City '{$cityName}' not found in {$stateName}";
                            continue;
                        }
                        $cityId = $city->id;

                        if ($areaName) {
                            $area = Area::where('city_id', $cityId)
                                ->whereRaw('LOWER(name) = ?', [strtolower($areaName)])
                                ->first();
                            if (!$area) {
                                $errors[] = "Row " . ($index + 2) . ": Area '{$areaName}' not found in {$cityName}";
                                continue;
                            }
                            $areaId = $area->id;
                        }
                    }
                }

                Company::create([
                    'name' => $companyName,
                    'address' => $address,
                    'state_id' => $stateId,
                    'city_id' => $cityId,
                    'area_id' => $areaId,
                    'pin_code' => $pinCode,
                    'country' => 'India',
                    'contact_number_1' => $contact1,
                    'email_1' => $email1,
                    'is_active' => true,
                ]);

                $imported++;
            }

            $message = "{$imported} companies imported successfully";
            if (count($errors) > 0) {
                $message .= ". " . count($errors) . " rows skipped.";
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Company upload failed: ' . $e->getMessage());
            return back()->with('error', 'Excel upload failed: ' . $e->getMessage());
        }
    }
}
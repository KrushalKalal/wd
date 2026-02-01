<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Zone;
use App\Models\State;
use App\Models\City;
use App\Models\Area;
use App\Models\Store;
use App\Models\EmployeeStoreAssignment;
use App\Helpers\RoleAccessHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class EmployeeMasterController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with([
            'user.roles',
            'company',
            'branch',
            'department',
            'zone',
            'state',
            'city',
            'area',
            'manager',
            'activeStoreAssignments.store'
        ]);

        // Apply role-based filter
        $query = RoleAccessHelper::applyRoleFilter($query);

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email_1', 'like', '%' . $request->search . '%')
                    ->orWhere('contact_number_1', 'like', '%' . $request->search . '%')
                    ->orWhere('designation', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by company
        if ($request->has('company_id') && $request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        // Filter by branch
        if ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by department
        if ($request->has('dept_id') && $request->dept_id) {
            $query->where('dept_id', $request->dept_id);
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
        $employees = $query->orderBy('name')->paginate($perPage);

        // Add stores_count and format data
        $employees->getCollection()->transform(function ($employee) {
            $employee->stores_count = $employee->activeStoreAssignments->count();
            $employee->role_name = $employee->user->roles->first()?->name ?? 'N/A';
            $employee->manager_name = $employee->manager?->name ?? 'N/A';
            return $employee;
        });

        $companies = Company::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $branches = Branch::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $departments = Department::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        $stateIds = RoleAccessHelper::getAccessibleStateIds();
        $states = State::whereIn('id', $stateIds)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('EmployeeMaster/Index', [
            'records' => $employees,
            'companies' => $companies,
            'branches' => $branches,
            'departments' => $departments,
            'states' => $states,
            'filters' => [
                'search' => $request->search,
                'company_id' => $request->company_id,
                'branch_id' => $request->branch_id,
                'dept_id' => $request->dept_id,
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
        $branches = Branch::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $departments = Department::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        $zoneIds = RoleAccessHelper::getAccessibleZoneIds();
        $zones = Zone::whereIn('id', $zoneIds)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $stateIds = RoleAccessHelper::getAccessibleStateIds();
        $states = State::whereIn('id', $stateIds)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $employees = Employee::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $roles = Role::all();

        return Inertia::render('EmployeeMaster/Form', [
            'companies' => $companies,
            'branches' => $branches,
            'departments' => $departments,
            'zones' => $zones,
            'states' => $states,
            'employees' => $employees,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
            'designation' => 'nullable|string|max:255',
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'dept_id' => 'nullable|exists:departments,id',
            'zone_id' => 'nullable|exists:zones,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'pin_code' => 'nullable|string|max:10',
            'address' => 'nullable|string',
            'contact_number_1' => 'nullable|string|max:20',
            'contact_number_2' => 'nullable|string|max:20',
            'email_1' => 'nullable|email',
            'email_2' => 'nullable|email',
            'aadhar_number' => 'nullable|string|max:20',
            'aadhar_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'employee_image' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'dob' => 'nullable|date',
            'doj' => 'nullable|date',
            'reporting_to' => 'nullable|exists:employees,id',
        ]);

        try {
            DB::beginTransaction();

            // Create User
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Assign Role
            $role = Role::findOrFail($request->role_id);
            $user->assignRole($role);

            // Prepare employee data
            $employeeData = $request->except(['password', 'email', 'role_id', 'aadhar_image', 'employee_image']);
            $employeeData['user_id'] = $user->id;
            $employeeData['is_active'] = true;

            // Handle Aadhar Image Upload
            if ($request->hasFile('aadhar_image')) {
                $employeeFolder = 'employees/' . Str::slug($request->name);
                $file = $request->file('aadhar_image');
                $fileName = 'aadhar_' . time() . '.' . $file->getClientOriginalExtension();
                $aadharPath = $file->storeAs($employeeFolder, $fileName, 'public');
                $employeeData['aadhar_image'] = $aadharPath;
            }

            // Handle Employee Image Upload
            if ($request->hasFile('employee_image')) {
                $employeeFolder = 'employees/' . Str::slug($request->name);
                $file = $request->file('employee_image');
                $fileName = 'profile_' . time() . '.' . $file->getClientOriginalExtension();
                $imagePath = $file->storeAs($employeeFolder, $fileName, 'public');
                $employeeData['employee_image'] = $imagePath;
            }

            Employee::create($employeeData);

            DB::commit();

            return redirect()->route('employee-master.index')
                ->with('success', 'Employee created successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Employee creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create employee: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit($id)
    {
        $employee = Employee::with(['user.roles', 'company', 'branch', 'department', 'zone', 'state', 'city', 'area', 'manager'])
            ->findOrFail($id);

        // Format for form
        $employee->email = $employee->user->email;
        $employee->role_id = $employee->user->roles->first()?->id;

        $companies = Company::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $branches = Branch::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $departments = Department::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        $zoneIds = RoleAccessHelper::getAccessibleZoneIds();
        $zones = Zone::whereIn('id', $zoneIds)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $stateIds = RoleAccessHelper::getAccessibleStateIds();
        $states = State::whereIn('id', $stateIds)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $employees = Employee::where('is_active', true)->where('id', '!=', $id)->select('id', 'name')->orderBy('name')->get();
        $roles = Role::all();

        return Inertia::render('EmployeeMaster/Form', [
            'employee' => $employee,
            'companies' => $companies,
            'branches' => $branches,
            'departments' => $departments,
            'zones' => $zones,
            'states' => $states,
            'employees' => $employees,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $employee->user_id,
            'password' => 'nullable|string|min:8',
            'role_id' => 'required|exists:roles,id',
            'designation' => 'nullable|string|max:255',
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'dept_id' => 'nullable|exists:departments,id',
            'zone_id' => 'nullable|exists:zones,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'aadhar_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'employee_image' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // Update User
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
            ];
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }
            $employee->user->update($userData);

            // Update Role
            $role = Role::findOrFail($request->role_id);
            $employee->user->syncRoles([$role]);

            // Update Employee
            $employeeData = $request->except(['password', 'email', 'role_id', 'aadhar_image', 'employee_image']);

            // Handle Aadhar Image Upload
            if ($request->hasFile('aadhar_image')) {
                if ($employee->aadhar_image && Storage::disk('public')->exists($employee->aadhar_image)) {
                    Storage::disk('public')->delete($employee->aadhar_image);
                }

                $employeeFolder = 'employees/' . Str::slug($request->name);
                $file = $request->file('aadhar_image');
                $fileName = 'aadhar_' . time() . '.' . $file->getClientOriginalExtension();
                $aadharPath = $file->storeAs($employeeFolder, $fileName, 'public');
                $employeeData['aadhar_image'] = $aadharPath;
            }

            // Handle Employee Image Upload
            if ($request->hasFile('employee_image')) {
                if ($employee->employee_image && Storage::disk('public')->exists($employee->employee_image)) {
                    Storage::disk('public')->delete($employee->employee_image);
                }

                $employeeFolder = 'employees/' . Str::slug($request->name);
                $file = $request->file('employee_image');
                $fileName = 'profile_' . time() . '.' . $file->getClientOriginalExtension();
                $imagePath = $file->storeAs($employeeFolder, $fileName, 'public');
                $employeeData['employee_image'] = $imagePath;
            }

            $employee->update($employeeData);

            DB::commit();

            return redirect()->route('employee-master.index')
                ->with('success', 'Employee updated successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Employee update failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update employee: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $employee = Employee::findOrFail($id);

            // Delete images
            if ($employee->aadhar_image && Storage::disk('public')->exists($employee->aadhar_image)) {
                Storage::disk('public')->delete($employee->aadhar_image);
            }
            if ($employee->employee_image && Storage::disk('public')->exists($employee->employee_image)) {
                Storage::disk('public')->delete($employee->employee_image);
            }

            // Delete folder if empty
            $employeeFolder = 'employees/' . Str::slug($employee->name);
            if (Storage::disk('public')->exists($employeeFolder)) {
                $files = Storage::disk('public')->files($employeeFolder);
                if (empty($files)) {
                    Storage::disk('public')->deleteDirectory($employeeFolder);
                }
            }

            // Delete user (cascades to employee)
            $employee->user->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Employee deleted successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Employee deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete employee');
        }
    }

    public function toggleStatus($id)
    {
        try {
            $employee = Employee::findOrFail($id);
            $employee->is_active = !$employee->is_active;
            $employee->save();

            return redirect()->back()->with('success', 'Employee status updated successfully');
        } catch (\Throwable $e) {
            Log::error('Employee toggle failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update employee status');
        }
    }

    // Store Assignment Methods
    public function getAssignedStores($id)
    {
        $employee = Employee::findOrFail($id);
        $assignments = EmployeeStoreAssignment::where('employee_id', $id)
            ->where('is_active', true)
            ->with('store')
            ->get();

        return response()->json($assignments);
    }

    public function assignStores(Request $request, $id)
    {
        $request->validate([
            'store_ids' => 'required|array',
            'store_ids.*' => 'exists:stores,id',
        ]);

        try {
            DB::beginTransaction();

            $employee = Employee::findOrFail($id);

            // Deactivate all current assignments
            EmployeeStoreAssignment::where('employee_id', $id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'removed_date' => now()
                ]);

            // Create new assignments
            foreach ($request->store_ids as $storeId) {
                EmployeeStoreAssignment::create([
                    'employee_id' => $id,
                    'store_id' => $storeId,
                    'assigned_date' => now(),
                    'is_active' => true,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stores assigned successfully'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Store assignment failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign stores'
            ], 500);
        }
    }

    public function removeStoreAssignment($id, $assignmentId)
    {
        try {
            $assignment = EmployeeStoreAssignment::where('employee_id', $id)
                ->where('id', $assignmentId)
                ->firstOrFail();

            $assignment->update([
                'is_active' => false,
                'removed_date' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Store assignment removed successfully'
            ]);
        } catch (\Throwable $e) {
            Log::error('Remove assignment failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove store assignment'
            ], 500);
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

            $zoneIds = RoleAccessHelper::getAccessibleZoneIds();
            $zones = Zone::whereIn('id', $zoneIds)
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name')
                ->toArray();

            $companies = Company::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
            $branches = Branch::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
            $departments = Department::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
            $roles = Role::orderBy('name')->pluck('name')->toArray();
            $employees = Employee::where('is_active', true)->orderBy('name')->pluck('name')->toArray();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Employees');

            // Headers
            $headers = [
                'Employee Name *',
                'Email (Login) *',
                'Password *',
                'Role *',
                'Designation',
                'Company',
                'Branch',
                'Department',
                'Zone',
                'State',
                'City',
                'Area',
                'Pin Code',
                'Address',
                'Contact Number 1',
                'Contact Number 2',
                'Email 1',
                'Email 2',
                'Aadhar Number',
                'Date of Birth (YYYY-MM-DD)',
                'Date of Joining (YYYY-MM-DD)',
                'Reporting To (Manager Name)',
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
            $sheet->getStyle('A1:V1')->applyFromArray($headerStyle);

            // Create hidden data sheet
            $dataSheet = $spreadsheet->createSheet();
            $dataSheet->setTitle('Data');

            // Dropdown lists
            $roleList = '"' . implode(',', $roles) . '"';
            $companyList = '"' . implode(',', $companies) . '"';
            $branchList = '"' . implode(',', $branches) . '"';
            $deptList = '"' . implode(',', $departments) . '"';
            $zoneList = '"' . implode(',', $zones) . '"';
            $managerList = '"' . implode(',', $employees) . '"';

            // States dropdown
            $stateNames = $states->pluck('name')->toArray();
            $stateList = '"' . implode(',', $stateNames) . '"';

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
                // Role dropdown (Column D)
                $validation = $sheet->getCell('D' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                $validation->setFormula1($roleList);

                // Company dropdown (Column F)
                if (!empty($companies)) {
                    $validation = $sheet->getCell('F' . $row)->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1($companyList);
                }

                // Branch dropdown (Column G)
                if (!empty($branches)) {
                    $validation = $sheet->getCell('G' . $row)->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1($branchList);
                }

                // Department dropdown (Column H)
                if (!empty($departments)) {
                    $validation = $sheet->getCell('H' . $row)->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1($deptList);
                }

                // Zone dropdown (Column I)
                if (!empty($zones)) {
                    $validation = $sheet->getCell('I' . $row)->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1($zoneList);
                }

                // State dropdown (Column J)
                $validation = $sheet->getCell('J' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1($stateList);

                // City dropdown (Column K) - dynamic
                $validation = $sheet->getCell('K' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('INDIRECT("Cities_"&SUBSTITUTE(J' . $row . '," ","_"))');

                // Area dropdown (Column L) - dynamic
                $validation = $sheet->getCell('L' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('INDIRECT("Areas_"&SUBSTITUTE(J' . $row . '," ","_")&"_"&SUBSTITUTE(K' . $row . '," ","_"))');

                // Manager dropdown (Column V)
                if (!empty($employees)) {
                    $validation = $sheet->getCell('V' . $row)->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1($managerList);
                }
            }

            // Auto-size columns
            foreach (range('A', 'V') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $spreadsheet->setActiveSheetIndex(0);

            $writer = new Xlsx($spreadsheet);
            $filename = 'employee_template_' . now()->format('Ymd_His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename={$filename}");
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;
        } catch (\Throwable $e) {
            Log::error('Employee template download failed: ' . $e->getMessage());
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
                $empName = trim($row['A'] ?? '');
                $email = trim($row['B'] ?? '');
                $password = trim($row['C'] ?? '');
                $roleName = trim($row['D'] ?? '');
                $designation = trim($row['E'] ?? '');
                $companyName = trim($row['F'] ?? '');
                $branchName = trim($row['G'] ?? '');
                $deptName = trim($row['H'] ?? '');
                $zoneName = trim($row['I'] ?? '');
                $stateName = trim($row['J'] ?? '');
                $cityName = trim($row['K'] ?? '');
                $areaName = trim($row['L'] ?? '');
                $pinCode = trim($row['M'] ?? '');
                $address = trim($row['N'] ?? '');
                $contact1 = trim($row['O'] ?? '');
                $contact2 = trim($row['P'] ?? '');
                $email1 = trim($row['Q'] ?? '');
                $email2 = trim($row['R'] ?? '');
                $aadhar = trim($row['S'] ?? '');
                $dob = trim($row['T'] ?? '');
                $doj = trim($row['U'] ?? '');
                $managerName = trim($row['V'] ?? '');

                if (!$empName || !$email || !$password || !$roleName) {
                    $errors[] = "Row " . ($index + 2) . ": Name, Email, Password, and Role are required";
                    continue;
                }

                // Check if email already exists
                $existingUser = User::where('email', $email)->first();
                if ($existingUser) {
                    $errors[] = "Row " . ($index + 2) . ": Email '{$email}' already exists";
                    continue;
                }

                // Find role
                $role = Role::whereRaw('LOWER(name) = ?', [strtolower($roleName)])->first();
                if (!$role) {
                    $errors[] = "Row " . ($index + 2) . ": Role '{$roleName}' not found";
                    continue;
                }

                // Find optional foreign keys
                $companyId = null;
                $branchId = null;
                $deptId = null;
                $zoneId = null;
                $stateId = null;
                $cityId = null;
                $areaId = null;
                $managerId = null;

                if ($companyName) {
                    $company = Company::whereRaw('LOWER(name) = ?', [strtolower($companyName)])->first();
                    $companyId = $company?->id;
                }

                if ($branchName) {
                    $branch = Branch::whereRaw('LOWER(name) = ?', [strtolower($branchName)])->first();
                    $branchId = $branch?->id;
                }

                if ($deptName) {
                    $dept = Department::whereRaw('LOWER(name) = ?', [strtolower($deptName)])->first();
                    $deptId = $dept?->id;
                }

                if ($zoneName) {
                    $zone = Zone::whereRaw('LOWER(name) = ?', [strtolower($zoneName)])->first();
                    $zoneId = $zone?->id;
                }

                if ($stateName) {
                    $state = State::whereRaw('LOWER(name) = ?', [strtolower($stateName)])->first();
                    $stateId = $state?->id;

                    if ($cityName && $stateId) {
                        $city = City::where('state_id', $stateId)
                            ->whereRaw('LOWER(name) = ?', [strtolower($cityName)])
                            ->first();
                        $cityId = $city?->id;

                        if ($areaName && $cityId) {
                            $area = Area::where('city_id', $cityId)
                                ->whereRaw('LOWER(name) = ?', [strtolower($areaName)])
                                ->first();
                            $areaId = $area?->id;
                        }
                    }
                }

                if ($managerName) {
                    $manager = Employee::whereRaw('LOWER(name) = ?', [strtolower($managerName)])->first();
                    $managerId = $manager?->id;
                }

                try {
                    DB::beginTransaction();

                    // Create User
                    $user = User::create([
                        'name' => $empName,
                        'email' => $email,
                        'password' => Hash::make($password),
                    ]);

                    // Assign Role
                    $user->assignRole($role);

                    // Create Employee
                    Employee::create([
                        'user_id' => $user->id,
                        'name' => $empName,
                        'designation' => $designation,
                        'company_id' => $companyId,
                        'branch_id' => $branchId,
                        'dept_id' => $deptId,
                        'zone_id' => $zoneId,
                        'state_id' => $stateId,
                        'city_id' => $cityId,
                        'area_id' => $areaId,
                        'pin_code' => $pinCode,
                        'address' => $address,
                        'contact_number_1' => $contact1,
                        'contact_number_2' => $contact2,
                        'email_1' => $email1,
                        'email_2' => $email2,
                        'aadhar_number' => $aadhar,
                        'dob' => $dob ?: null,
                        'doj' => $doj ?: null,
                        'reporting_to' => $managerId,
                        'is_active' => true,
                    ]);

                    DB::commit();
                    $imported++;
                } catch (\Exception $e) {
                    DB::rollBack();
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                }
            }

            $message = "{$imported} employees imported successfully";
            if (count($errors) > 0) {
                $message .= ". " . count($errors) . " rows skipped.";
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Employee upload failed: ' . $e->getMessage());
            return back()->with('error', 'Excel upload failed: ' . $e->getMessage());
        }
    }
}
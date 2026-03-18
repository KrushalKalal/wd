<?php

namespace App\Http\Controllers;

use App\Helpers\RoleAccessHelper;
use App\Models\Area;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeStoreAssignment;
use App\Models\State;
use App\Models\Store;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $query = RoleAccessHelper::applyRoleFilter($query);

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email_1', 'like', '%' . $request->search . '%')
                    ->orWhere('contact_number_1', 'like', '%' . $request->search . '%')
                    ->orWhere('designation', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('company_id') && $request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('dept_id') && $request->dept_id) {
            $query->where('dept_id', $request->dept_id);
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
        $employees = $query->orderBy('name')->paginate($perPage);

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
        $userLocation = RoleAccessHelper::getUserLocation();
        $locationLocks = RoleAccessHelper::getLocationLocks();

        // Only show roles this user can create
        $creatableRoleNames = RoleAccessHelper::getCreatableRoles();
        $roles = Role::whereIn('name', $creatableRoleNames)->get();

        $company = Company::where('is_active', true)->first();
        $branchQuery = Branch::where('is_active', true)->select('id', 'name');

        // Filter branches by role access
        $user = Auth::user();
        $currentEmployee = $user->employee;

        if ($user->hasRole('Zonal Head') && $currentEmployee?->zone_id) {
            $branchQuery->whereHas('state', function ($q) use ($currentEmployee) {
                $q->where('zone_id', $currentEmployee->zone_id);
            });
        } elseif ($user->hasRole('State Head') && $currentEmployee?->state_id) {
            $branchQuery->where('state_id', $currentEmployee->state_id);
        } elseif ($user->hasRole(['City Head', 'On/Off Trade Head']) && $currentEmployee?->city_id) {
            $branchQuery->where('city_id', $currentEmployee->city_id);
        }

        $branches = $branchQuery->orderBy('name')->get();
        $departments = Department::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        $areas = [];
        if ($userLocation['city_id']) {
            $areas = Area::where('city_id', $userLocation['city_id'])
                ->where('is_active', true)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        }

        return Inertia::render('EmployeeMaster/Form', [
            'company' => $company,
            'branches' => $branches,
            'departments' => $departments,
            'roles' => $roles,
            'managers' => [],
            'userLocation' => $userLocation,
            'locationLocks' => $locationLocks,
            'areas' => $areas,
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
            'aadhar_number' => 'nullable|string|max:20',
            'aadhar_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'employee_image' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'dob' => 'nullable|date',
            'doj' => 'nullable|date',
            'reporting_to' => 'nullable|exists:employees,id',
        ]);

        try {
            // Validate role is creatable by logged in user
            $role = Role::findOrFail($request->role_id);
            $creatableRoles = RoleAccessHelper::getCreatableRoles();

            if (!in_array($role->name, $creatableRoles)) {
                return redirect()->back()
                    ->with('error', 'You do not have permission to create this role.')
                    ->withInput();
            }

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

            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $user->assignRole($role);

            $employeeData = $request->except([
                'password',
                'email',
                'role_id',
                'aadhar_image',
                'employee_image'
            ]);

            $employeeData['user_id'] = $user->id;
            $employeeData['is_active'] = true;
            $employeeData['country'] = 'India';

            // Inject locked location from logged in user
            $locationLocks = RoleAccessHelper::getLocationLocks();
            $userLocation = RoleAccessHelper::getUserLocation();

            if ($locationLocks['zone_id'])
                $employeeData['zone_id'] = $userLocation['zone_id'];
            if ($locationLocks['state_id'])
                $employeeData['state_id'] = $userLocation['state_id'];
            if ($locationLocks['city_id'])
                $employeeData['city_id'] = $userLocation['city_id'];

            // Single company pre-fill
            if (!$request->company_id) {
                $company = Company::where('is_active', true)->first();
                if ($company)
                    $employeeData['company_id'] = $company->id;
            }

            if ($request->hasFile('aadhar_image')) {
                $folder = 'employees/' . Str::slug($request->name);
                $file = $request->file('aadhar_image');
                $fileName = 'aadhar_' . time() . '.' . $file->getClientOriginalExtension();
                $employeeData['aadhar_image'] = $file->storeAs($folder, $fileName, 'public');
            }

            if ($request->hasFile('employee_image')) {
                $folder = 'employees/' . Str::slug($request->name);
                $file = $request->file('employee_image');
                $fileName = 'profile_' . time() . '.' . $file->getClientOriginalExtension();
                $employeeData['employee_image'] = $file->storeAs($folder, $fileName, 'public');
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
        $employee = Employee::with([
            'user.roles',
            'company',
            'branch',
            'department',
            'zone',
            'state',
            'city',
            'area',
            'manager'
        ])->findOrFail($id);

        $employee->email = $employee->user->email;
        $employee->role_id = $employee->user->roles->first()?->id;

        $userLocation = RoleAccessHelper::getUserLocation();
        $locationLocks = RoleAccessHelper::getLocationLocks();

        $creatableRoleNames = RoleAccessHelper::getCreatableRoles();
        $roles = Role::whereIn('name', $creatableRoleNames)->get();

        $company = Company::where('is_active', true)->first();
        $branchQuery = Branch::where('is_active', true)->select('id', 'name');

        // Filter branches by role access
        $user = Auth::user();
        $currentEmployee = $user->employee;

        if ($user->hasRole('Zonal Head') && $currentEmployee?->zone_id) {
            $branchQuery->whereHas('state', function ($q) use ($currentEmployee) {
                $q->where('zone_id', $currentEmployee->zone_id);
            });
        } elseif ($user->hasRole('State Head') && $currentEmployee?->state_id) {
            $branchQuery->where('state_id', $currentEmployee->state_id);
        } elseif ($user->hasRole(['City Head', 'On/Off Trade Head']) && $currentEmployee?->city_id) {
            $branchQuery->where('city_id', $currentEmployee->city_id);
        }

        $branches = $branchQuery->orderBy('name')->get();
        $departments = Department::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        $areas = [];
        if ($employee->city_id) {
            $areas = Area::where('city_id', $employee->city_id)
                ->where('is_active', true)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        }

        // Load managers for existing role
        $managers = [];
        $currentRole = $employee->user->roles->first();
        if ($currentRole) {
            $managers = $this->fetchManagersForRole($currentRole->id);
        }

        return Inertia::render('EmployeeMaster/Form', [
            'employee' => $employee,
            'company' => $company,
            'branches' => $branches,
            'departments' => $departments,
            'roles' => $roles,
            'managers' => $managers,
            'userLocation' => $userLocation,
            'locationLocks' => $locationLocks,
            'areas' => $areas,
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
            $accessCheck = RoleAccessHelper::validateLocationAccess(
                $request->state_id,
                $request->city_id
            );

            if (!$accessCheck['valid']) {
                return redirect()->back()
                    ->with('error', $accessCheck['message'])
                    ->withInput();
            }

            DB::beginTransaction();

            $userData = ['name' => $request->name, 'email' => $request->email];
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }
            $employee->user->update($userData);

            $role = Role::findOrFail($request->role_id);
            $employee->user->syncRoles([$role]);

            $employeeData = $request->except([
                'password',
                'email',
                'role_id',
                'aadhar_image',
                'employee_image'
            ]);

            $employeeData['country'] = 'India';

            $locationLocks = RoleAccessHelper::getLocationLocks();
            $userLocation = RoleAccessHelper::getUserLocation();

            if ($locationLocks['zone_id'])
                $employeeData['zone_id'] = $userLocation['zone_id'];
            if ($locationLocks['state_id'])
                $employeeData['state_id'] = $userLocation['state_id'];
            if ($locationLocks['city_id'])
                $employeeData['city_id'] = $userLocation['city_id'];

            if ($request->hasFile('aadhar_image')) {
                if ($employee->aadhar_image) {
                    Storage::disk('public')->delete($employee->aadhar_image);
                }
                $folder = 'employees/' . Str::slug($request->name);
                $file = $request->file('aadhar_image');
                $fileName = 'aadhar_' . time() . '.' . $file->getClientOriginalExtension();
                $employeeData['aadhar_image'] = $file->storeAs($folder, $fileName, 'public');
            }

            if ($request->hasFile('employee_image')) {
                if ($employee->employee_image) {
                    Storage::disk('public')->delete($employee->employee_image);
                }
                $folder = 'employees/' . Str::slug($request->name);
                $file = $request->file('employee_image');
                $fileName = 'profile_' . time() . '.' . $file->getClientOriginalExtension();
                $employeeData['employee_image'] = $file->storeAs($folder, $fileName, 'public');
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

    private function fetchManagersForRole(int $roleId): array
    {
        $role = Role::find($roleId);
        if (!$role)
            return [];

        $managerRoles = RoleAccessHelper::getManagerRoles($role->name);
        if (empty($managerRoles))
            return [];

        $userLocation = RoleAccessHelper::getUserLocation();
        $user = Auth::user();

        $query = Employee::whereHas('user.roles', function ($q) use ($managerRoles) {
            $q->whereIn('name', $managerRoles);
        })->where('is_active', true);

        if ($user->hasRole('Zonal Head') && $userLocation['zone_id']) {
            $query->where('zone_id', $userLocation['zone_id']);
        } elseif ($user->hasRole('State Head') && $userLocation['state_id']) {
            $query->where('state_id', $userLocation['state_id']);
        } elseif ($user->hasRole(['City Head', 'On/Off Trade Head']) && $userLocation['city_id']) {
            $query->where('city_id', $userLocation['city_id']);
        }

        return $query->select('id', 'name')->orderBy('name')->get()->toArray();
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $employee = Employee::findOrFail($id);

            if ($employee->aadhar_image && Storage::disk('public')->exists($employee->aadhar_image)) {
                Storage::disk('public')->delete($employee->aadhar_image);
            }
            if ($employee->employee_image && Storage::disk('public')->exists($employee->employee_image)) {
                Storage::disk('public')->delete($employee->employee_image);
            }

            $employeeFolder = 'employees/' . Str::slug($employee->name);
            if (Storage::disk('public')->exists($employeeFolder)) {
                $files = Storage::disk('public')->files($employeeFolder);
                if (empty($files)) {
                    Storage::disk('public')->deleteDirectory($employeeFolder);
                }
            }

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

    public function togglePromocode(Employee $employee)
    {
        $employee->promocode_active = !$employee->promocode_active;
        $employee->save();
        return response()->json([
            'success' => true,
            'promocode_active' => $employee->promocode_active,
        ]);
    }

    public function checkPromocodeAvailability(Request $request)
    {
        $request->validate([
            'promocode' => 'required|string',
            'employee_id' => 'nullable|exists:employees,id',
        ]);

        $query = Employee::where('promocode', $request->promocode);
        if ($request->employee_id) {
            $query->where('id', '!=', $request->employee_id);
        }

        $exists = $query->exists();
        return response()->json([
            'available' => !$exists,
            'message' => $exists ? 'Promocode already in use' : 'Promocode is available',
        ]);
    }

    public function getAssignedStores($id)
    {
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

            EmployeeStoreAssignment::where('employee_id', $id)
                ->where('is_active', true)
                ->update(['is_active' => false, 'removed_date' => now()]);

            foreach ($request->store_ids as $storeId) {
                EmployeeStoreAssignment::create([
                    'employee_id' => $id,
                    'store_id' => $storeId,
                    'assigned_date' => now(),
                    'is_active' => true,
                ]);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Stores assigned successfully']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Store assignment failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to assign stores'], 500);
        }
    }

    public function removeStoreAssignment($id, $assignmentId)
    {
        try {
            EmployeeStoreAssignment::where('employee_id', $id)
                ->where('id', $assignmentId)
                ->firstOrFail()
                ->update(['is_active' => false, 'removed_date' => now()]);

            return response()->json(['success' => true, 'message' => 'Store assignment removed successfully']);
        } catch (\Throwable $e) {
            Log::error('Remove assignment failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to remove store assignment'], 500);
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
            $zones = Zone::whereIn('id', $zoneIds)->where('is_active', true)->orderBy('name')->pluck('name')->toArray();
            $companies = Company::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
            $branches = Branch::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
            $departments = Department::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
            $roles = Role::orderBy('name')->pluck('name')->toArray();
            $employees = Employee::where('is_active', true)->orderBy('name')->pluck('name')->toArray();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Employees');

            $headers = [
                'Employee Name *',
                'Email (Login) *',
                'Password *',
                'Role *',
                'Designation',
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

            $headerStyle = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE0E0E0'],
                ],
            ];
            $sheet->getStyle('A1:T1')->applyFromArray($headerStyle);

            $dataSheet = $spreadsheet->createSheet();
            $dataSheet->setTitle('Data');

            $roleList = '"' . implode(',', $roles) . '"';
            $branchList = '"' . implode(',', $branches) . '"';
            $deptList = '"' . implode(',', $departments) . '"';
            $zoneList = '"' . implode(',', $zones) . '"';
            $managerList = '"' . implode(',', $employees) . '"';
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
                // Role Column D
                $validation = $sheet->getCell('D' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                $validation->setFormula1($roleList);

                // Branch Column F
                if (!empty($branches)) {
                    $validation = $sheet->getCell('F' . $row)->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1($branchList);
                }

                // Department Column G
                if (!empty($departments)) {
                    $validation = $sheet->getCell('G' . $row)->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1($deptList);
                }

                // Zone Column H
                if (!empty($zones)) {
                    $validation = $sheet->getCell('H' . $row)->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1($zoneList);
                }

                // State Column I
                $validation = $sheet->getCell('I' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1($stateList);

                // City Column J
                $validation = $sheet->getCell('J' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('INDIRECT("Cities_"&SUBSTITUTE(I' . $row . '," ","_"))');

                // Area Column K
                $validation = $sheet->getCell('K' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('INDIRECT("Areas_"&SUBSTITUTE(I' . $row . '," ","_")&"_"&SUBSTITUTE(J' . $row . '," ","_"))');

                // Manager Column T
                if (!empty($employees)) {
                    $validation = $sheet->getCell('T' . $row)->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1($managerList);
                }
            }

            foreach (range('A', 'T') as $col) {
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

            // Single company
            $company = Company::where('is_active', true)->first();

            foreach (array_slice($rows, 1) as $index => $row) {
                $empName = trim($row['A'] ?? '');
                $email = trim($row['B'] ?? '');
                $password = trim($row['C'] ?? '');
                $roleName = trim($row['D'] ?? '');
                $designation = trim($row['E'] ?? '');
                $branchName = trim($row['F'] ?? '');
                $deptName = trim($row['G'] ?? '');
                $zoneName = trim($row['H'] ?? '');
                $stateName = trim($row['I'] ?? '');
                $cityName = trim($row['J'] ?? '');
                $areaName = trim($row['K'] ?? '');
                $pinCode = trim($row['L'] ?? '');
                $address = trim($row['M'] ?? '');
                $contact1 = trim($row['N'] ?? '');
                $contact2 = trim($row['O'] ?? '');
                $email1 = trim($row['P'] ?? '');
                $aadhar = trim($row['Q'] ?? '');
                $dob = trim($row['R'] ?? '');
                $doj = trim($row['S'] ?? '');
                $managerName = trim($row['T'] ?? '');

                if (!$empName || !$email || !$password || !$roleName) {
                    $errors[] = "Row " . ($index + 2) . ": Name, Email, Password, and Role are required";
                    continue;
                }

                $existingUser = User::where('email', $email)->first();
                if ($existingUser) {
                    $errors[] = "Row " . ($index + 2) . ": Email '{$email}' already exists";
                    continue;
                }

                $role = Role::whereRaw('LOWER(name) = ?', [strtolower($roleName)])->first();
                if (!$role) {
                    $errors[] = "Row " . ($index + 2) . ": Role '{$roleName}' not found";
                    continue;
                }

                $branchId = null;
                $deptId = null;
                $zoneId = null;
                $stateId = null;
                $cityId = null;
                $areaId = null;
                $managerId = null;

                if ($branchName) {
                    $branch = Branch::whereRaw('LOWER(name) = ?', [strtolower($branchName)])->first();
                    $branchId = $branch?->id;
                }

                if ($deptName) {
                    $dept = \App\Models\Department::whereRaw('LOWER(name) = ?', [strtolower($deptName)])->first();
                    $deptId = $dept?->id;
                }

                if ($zoneName) {
                    $zone = \App\Models\Zone::whereRaw('LOWER(name) = ?', [strtolower($zoneName)])->first();
                    $zoneId = $zone?->id;
                }

                if ($stateName) {
                    $state = \App\Models\State::whereRaw('LOWER(name) = ?', [strtolower($stateName)])->first();
                    $stateId = $state?->id;

                    if ($cityName && $stateId) {
                        $city = \App\Models\City::where('state_id', $stateId)->whereRaw('LOWER(name) = ?', [strtolower($cityName)])->first();
                        $cityId = $city?->id;

                        if ($areaName && $cityId) {
                            $area = \App\Models\Area::where('city_id', $cityId)->whereRaw('LOWER(name) = ?', [strtolower($areaName)])->first();
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

                    $user = User::create([
                        'name' => $empName,
                        'email' => $email,
                        'password' => Hash::make($password),
                    ]);

                    $user->assignRole($role);

                    Employee::create([
                        'user_id' => $user->id,
                        'name' => $empName,
                        'designation' => $designation,
                        'company_id' => $company?->id,
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
                        'aadhar_number' => $aadhar,
                        'dob' => $dob ?: null,
                        'doj' => $doj ?: null,
                        'reporting_to' => $managerId,
                        'country' => 'India',
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
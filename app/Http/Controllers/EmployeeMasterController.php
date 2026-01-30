<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Department;
use App\Models\State;
use App\Models\Store;
use App\Models\EmployeeStoreAssignment;
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

class EmployeeMasterController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with([
            'user.roles',
            'company',
            'branch',
            'department',
            'state',
            'city',
            'area',
            'manager',
            'activeStoreAssignments.store'
        ])->where('is_active', true);

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email_1', 'like', '%' . $request->search . '%')
                    ->orWhere('contact_number_1', 'like', '%' . $request->search . '%')
                    ->orWhere('designation', 'like', '%' . $request->search . '%');
            });
        }

        // Filters
        if ($request->has('company_id') && $request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('department_id') && $request->department_id) {
            $query->where('dept_id', $request->department_id);
        }

        if ($request->has('state_id') && $request->state_id) {
            $query->where('state_id', $request->state_id);
        }

        $perPage = $request->get('per_page', 10);
        $employees = $query->orderBy('name')->paginate($perPage);

        $companies = Company::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $departments = Department::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $states = State::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('EmployeeMaster/Index', [
            'records' => $employees,
            'companies' => $companies,
            'departments' => $departments,
            'states' => $states,
            'filters' => [
                'search' => $request->search,
                'company_id' => $request->company_id,
                'department_id' => $request->department_id,
                'state_id' => $request->state_id,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create()
    {
        $companies = Company::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $branches = Branch::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $departments = Department::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $states = State::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $employees = Employee::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $roles = Role::all();

        return Inertia::render('EmployeeMaster/Form', [
            'companies' => $companies,
            'branches' => $branches,
            'departments' => $departments,
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
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'dept_id' => 'nullable|exists:departments,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'contact_number_1' => 'nullable|string|max:20',
            'email_1' => 'nullable|email',
            'designation' => 'nullable|string|max:255',
            'dob' => 'nullable|date',
            'doj' => 'nullable|date',
            'reporting_to' => 'nullable|exists:employees,id',
            'aadhar_number' => 'nullable|string|max:20',
            'aadhar_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'employee_image' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
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

            // Handle Image Uploads
            $employeeData = $request->except(['password', 'role_id', 'aadhar_image', 'employee_image']);
            $employeeData['user_id'] = $user->id;
            $employeeData['is_active'] = true;

            // Upload Aadhar Image
            if ($request->hasFile('aadhar_image')) {
                $employeeFolder = 'employees/' . Str::slug($request->name);
                $file = $request->file('aadhar_image');
                $fileName = 'aadhar_' . time() . '.' . $file->getClientOriginalExtension();
                $aadharPath = $file->storeAs($employeeFolder, $fileName, 'public');
                $employeeData['aadhar_image'] = $aadharPath;
            }

            // Upload Employee Image
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
                ->with('error', 'Failed to create employee')
                ->withInput();
        }
    }

    public function edit($id)
    {
        $employee = Employee::with(['user.roles', 'company', 'branch', 'department', 'state', 'city', 'area', 'manager'])
            ->findOrFail($id);

        $companies = Company::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $branches = Branch::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $departments = Department::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $states = State::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $employees = Employee::where('is_active', true)->where('id', '!=', $id)->select('id', 'name')->orderBy('name')->get();
        $roles = Role::all();

        return Inertia::render('EmployeeMaster/Form', [
            'employee' => $employee,
            'companies' => $companies,
            'branches' => $branches,
            'departments' => $departments,
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
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'dept_id' => 'nullable|exists:departments,id',
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
            $employeeData = $request->except(['password', 'role_id', 'aadhar_image', 'employee_image']);

            // Handle Aadhar Image Upload
            if ($request->hasFile('aadhar_image')) {
                // Delete old image
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
                // Delete old image
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
                ->with('error', 'Failed to update employee')
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

            // Delete employee folder if empty
            $employeeFolder = 'employees/' . Str::slug($employee->name);
            if (Storage::disk('public')->exists($employeeFolder)) {
                $files = Storage::disk('public')->files($employeeFolder);
                if (empty($files)) {
                    Storage::disk('public')->deleteDirectory($employeeFolder);
                }
            }

            // Delete user (will cascade delete employee)
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

    // Get assigned stores for an employee
    public function getAssignedStores($id)
    {
        $employee = Employee::findOrFail($id);
        $assignments = EmployeeStoreAssignment::where('employee_id', $id)
            ->where('is_active', true)
            ->with('store')
            ->get();

        return response()->json($assignments);
    }

    // Assign stores to employee
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

    // Remove store assignment
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
}
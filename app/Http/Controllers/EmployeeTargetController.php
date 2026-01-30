<?php

namespace App\Http\Controllers;

use App\Models\EmployeeTarget;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class EmployeeTargetController extends Controller
{
    public function index(Request $request)
    {
        $query = EmployeeTarget::with(['employee.user', 'employee.company', 'employee.branch']);

        // Search
        if ($request->has('search') && $request->search) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by employee
        if ($request->has('employee_id') && $request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by year
        if ($request->has('year') && $request->year) {
            $query->where('year', $request->year);
        }

        // Filter by month
        if ($request->has('month') && $request->month) {
            $query->where('month', $request->month);
        }

        $perPage = $request->get('per_page', 10);
        $targets = $query->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->paginate($perPage);

        // Add computed fields
        $targets->getCollection()->transform(function ($target) {
            $target->visit_completion = $target->visit_target > 0
                ? round(($target->visits_completed / $target->visit_target) * 100, 2)
                : 0;
            $target->sales_completion = $target->sales_target > 0
                ? round(($target->sales_achieved / $target->sales_target) * 100, 2)
                : 0;
            $target->month_name = date('F', mktime(0, 0, 0, $target->month, 1));
            return $target;
        });

        $employees = Employee::where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('EmployeeTarget/Index', [
            'records' => $targets,
            'employees' => $employees,
            'filters' => [
                'search' => $request->search,
                'employee_id' => $request->employee_id,
                'status' => $request->status,
                'year' => $request->year,
                'month' => $request->month,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create()
    {
        $employees = Employee::where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('EmployeeTarget/Form', [
            'employees' => $employees,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
            'visit_target' => 'required|integer|min:0',
            'sales_target' => 'required|numeric|min:0',
        ]);

        try {
            // Check if target already exists
            $existing = EmployeeTarget::where('employee_id', $request->employee_id)
                ->where('month', $request->month)
                ->where('year', $request->year)
                ->first();

            if ($existing) {
                return redirect()->back()
                    ->with('error', 'Target already exists for this employee for the selected month/year')
                    ->withInput();
            }

            EmployeeTarget::create([
                'employee_id' => $request->employee_id,
                'month' => $request->month,
                'year' => $request->year,
                'visit_target' => $request->visit_target,
                'visits_completed' => 0,
                'sales_target' => $request->sales_target,
                'sales_achieved' => 0,
                'status' => 'pending',
            ]);

            return redirect()->route('employee-target.index')
                ->with('success', 'Employee target created successfully');
        } catch (\Throwable $e) {
            Log::error('Employee target creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create employee target')
                ->withInput();
        }
    }

    public function edit($id)
    {
        $target = EmployeeTarget::with(['employee'])->findOrFail($id);
        $employees = Employee::where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('EmployeeTarget/Form', [
            'target' => $target,
            'employees' => $employees,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
            'visit_target' => 'required|integer|min:0',
            'sales_target' => 'required|numeric|min:0',
            'visits_completed' => 'nullable|integer|min:0',
            'sales_achieved' => 'nullable|numeric|min:0',
        ]);

        try {
            $target = EmployeeTarget::findOrFail($id);

            // Check if changing to existing combination
            $existing = EmployeeTarget::where('employee_id', $request->employee_id)
                ->where('month', $request->month)
                ->where('year', $request->year)
                ->where('id', '!=', $id)
                ->first();

            if ($existing) {
                return redirect()->back()
                    ->with('error', 'Target already exists for this employee for the selected month/year')
                    ->withInput();
            }

            $target->update([
                'employee_id' => $request->employee_id,
                'month' => $request->month,
                'year' => $request->year,
                'visit_target' => $request->visit_target,
                'sales_target' => $request->sales_target,
                'visits_completed' => $request->visits_completed ?? $target->visits_completed,
                'sales_achieved' => $request->sales_achieved ?? $target->sales_achieved,
            ]);

            // Update status based on completion
            $target->updateStatus();

            return redirect()->route('employee-target.index')
                ->with('success', 'Employee target updated successfully');
        } catch (\Throwable $e) {
            Log::error('Employee target update failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update employee target')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $target = EmployeeTarget::findOrFail($id);
            $target->delete();

            return redirect()->back()
                ->with('success', 'Employee target deleted successfully');
        } catch (\Throwable $e) {
            Log::error('Employee target deletion failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to delete employee target');
        }
    }
}
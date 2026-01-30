<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeTarget;
use Illuminate\Http\Request;

class ApiTargetController extends Controller
{
    public function getCurrentMonthTarget(Request $request)
    {
        $employee = $request->user()->employee;

        $target = EmployeeTarget::where('employee_id', $employee->id)
            ->where('month', now()->month)
            ->where('year', now()->year)
            ->first();

        if (!$target) {
            return response()->json([
                'success' => true,
                'message' => 'No target set for current month',
                'data' => null
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $target->month,
                'year' => $target->year,
                'visit_target' => $target->visit_target,
                'visits_completed' => $target->visits_completed,
                'visit_completion' => $target->visit_completion_percentage . '%',
                'sales_target' => $target->sales_target,
                'sales_achieved' => $target->sales_achieved,
                'sales_completion' => $target->sales_completion_percentage . '%',
                'status' => $target->status,
            ]
        ]);
    }
}
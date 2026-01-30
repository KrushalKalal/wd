<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StoreVisit;
use App\Models\EmployeeTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiVisitController extends Controller
{
    public function checkIn(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $employee = $request->user()->employee;

        // Check if store is assigned to employee
        $isAssigned = $employee->assignedStores()->where('stores.id', $request->store_id)->exists();
        if (!$isAssigned) {
            return response()->json([
                'success' => false,
                'message' => 'Store is not assigned to you'
            ], 403);
        }

        // Check if already checked in today
        $existingVisit = StoreVisit::where('employee_id', $employee->id)
            ->where('store_id', $request->store_id)
            ->whereDate('visit_date', today())
            ->whereNull('check_out_time')
            ->first();

        if ($existingVisit) {
            return response()->json([
                'success' => false,
                'message' => 'Already checked in to this store today',
                'data' => $existingVisit
            ], 400);
        }

        try {
            $visit = StoreVisit::create([
                'employee_id' => $employee->id,
                'store_id' => $request->store_id,
                'visit_date' => today(),
                'check_in_time' => now()->format('H:i:s'),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'status' => 'checked_in',
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Checked in successfully',
                'data' => $visit
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Check-in failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkOut(Request $request, $id)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'visit_summary' => 'nullable|string',
        ]);

        $employee = $request->user()->employee;

        $visit = StoreVisit::where('id', $id)
            ->where('employee_id', $employee->id)
            ->whereNull('check_out_time')
            ->firstOrFail();

        try {
            DB::beginTransaction();

            $visit->update([
                'check_out_time' => now()->format('H:i:s'),
                'status' => 'completed',
                'visit_summary' => $request->visit_summary,
            ]);

            // Update employee target
            $target = EmployeeTarget::firstOrCreate([
                'employee_id' => $employee->id,
                'month' => now()->month,
                'year' => now()->year,
            ], [
                'visit_target' => 0,
                'visits_completed' => 0,
                'sales_target' => 0,
                'sales_achieved' => 0,
            ]);

            $target->increment('visits_completed');
            $target->updateStatus();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Checked out successfully',
                'data' => $visit->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Check-out failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTodayVisit(Request $request)
    {
        $employee = $request->user()->employee;

        $visit = StoreVisit::where('employee_id', $employee->id)
            ->whereDate('visit_date', today())
            ->whereNull('check_out_time')
            ->with('store')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $visit
        ]);
    }
}
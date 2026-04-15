<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StoreFlag;
use App\Models\StoreVisit;
use Illuminate\Http\Request;

class ApiStoreFlagController extends Controller
{
    /**
     * POST /api/stores/{id}/flag
     * Toggle flag on a store. If unresolved flag exists → resolve it (unflag).
     * If no active flag → create one (flag).
     */
    public function toggle(Request $request, $storeId)
    {
        $request->validate([
            'flag_note' => 'nullable|string|max:500',
            'visit_id' => 'nullable|exists:store_visits,id',
        ]);

        $employee = $request->user()->employee;

        // Check store is assigned to this employee
        $isAssigned = $employee->assignedStores()
            ->where('stores.id', $storeId)
            ->exists();

        if (!$isAssigned) {
            return response()->json([
                'success' => false,
                'message' => 'Store is not assigned to you.',
            ], 403);
        }

        // Check existing unresolved flag
        $existingFlag = StoreFlag::where('employee_id', $employee->id)
            ->where('store_id', $storeId)
            ->where('is_resolved', false)
            ->first();

        if ($existingFlag) {
            // Already flagged → unflag (resolve it)
            $existingFlag->update([
                'is_resolved' => true,
                'resolved_by' => $request->user()->id,
                'resolved_at' => now(),
                'resolved_note' => 'Unflagged by employee',
            ]);

            return response()->json([
                'success' => true,
                'is_flagged' => false,
                'message' => 'Store unflagged successfully.',
            ]);
        }

        // Not flagged → create new flag
        $flag = StoreFlag::create([
            'employee_id' => $employee->id,
            'store_id' => $storeId,
            'visit_id' => $request->visit_id,
            'flag_note' => $request->flag_note,
            'is_resolved' => false,
        ]);

        return response()->json([
            'success' => true,
            'is_flagged' => true,
            'message' => 'Store flagged successfully.',
            'flag_id' => $flag->id,
            'flag_note' => $flag->flag_note,
            'flagged_at' => $flag->created_at->toDateTimeString(),
        ]);
    }

    /**
     * GET /api/stores/flagged
     * All currently flagged stores for this employee.
     */
    public function getFlagged(Request $request)
    {
        $employee = $request->user()->employee;

        $flags = StoreFlag::where('employee_id', $employee->id)
            ->where('is_resolved', false)
            ->with('store:id,name,address,city_id,state_id')
            ->with('store.city:id,name')
            ->with('store.state:id,name')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($flag) => [
                'flag_id' => $flag->id,
                'store_id' => $flag->store_id,
                'store_name' => $flag->store->name,
                'city' => $flag->store->city?->name,
                'state' => $flag->store->state?->name,
                'flag_note' => $flag->flag_note,
                'flagged_at' => $flag->created_at->toDateTimeString(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $flags,
        ]);
    }
}
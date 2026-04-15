<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyPlan;
use App\Models\DailyPlanStore;
use App\Models\StoreVisit;
use App\Models\EmployeeTarget;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiVisitController extends Controller
{
    /**
     * POST /visits/check-in
     *
     * No daily plan or day-start required.
     * 
     * Only requirements:
     *  - Store must be assigned to employee OR match employee state+city
     *  - Employee must be within 25m of store (geofence)
     *  - Employee must not already be checked in to this store today
     *
     * Body:
     * {
     *   "store_id": 5,
     *   "latitude": 23.0225,
     *   "longitude": 72.5714,
     *   "daily_plan_store_id": null   // optional — only send if employee has a plan
     * }
     */
    public function checkIn(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'daily_plan_store_id' => 'nullable|exists:daily_plan_stores,id',
        ]);

        $employee = $request->user()->employee;

        // ── Store access check ──────────────────────────────────────
        // Allow if assigned OR if the store is in the same state+city as the employee
        $isAssigned = $employee->assignedStores()
            ->where('stores.id', $request->store_id)
            ->exists();

        if (!$isAssigned) {
            $storeInArea = Store::where('id', $request->store_id)
                ->where('state_id', $employee->state_id)
                ->where('city_id', $employee->city_id)
                ->exists();

            if (!$storeInArea) {
                return response()->json([
                    'success' => false,
                    'message' => 'This store is not assigned to you and is not in your area.',
                ], 403);
            }
        }

        // ── Geofence: employee must be within 25m of the store ──────────
        $store = Store::findOrFail($request->store_id);

        if ($store->latitude && $store->longitude) {
            $distanceMeters = $this->haversineDistance(
                (float) $request->latitude,
                (float) $request->longitude,
                (float) $store->latitude,
                (float) $store->longitude
            );

            if ($distanceMeters > 25) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are too far from the store to check in. You must be within 25 metres.',
                    'distance_meters' => (int) round($distanceMeters),
                    'allowed_meters' => 25,
                ], 422);
            }
        }

        // ── Duplicate check-in guard ──────────────────────────────────
        $existingVisit = StoreVisit::where('employee_id', $employee->id)
            ->where('store_id', $request->store_id)
            ->whereDate('visit_date', today())
            ->whereNull('check_out_time')
            ->first();

        if ($existingVisit) {
            return response()->json([
                'success' => false,
                'message' => 'You are already checked in to this store today.',
                'data' => $existingVisit,
            ], 400);
        }

        try {
            DB::beginTransaction();

            if ($store && !$store->is_active) {
                $store->is_active = true;
                $store->save();
            }

            $visit = StoreVisit::create([
                'employee_id' => $employee->id,
                'store_id' => $request->store_id,
                'visit_date' => today(),
                'check_in_time' => now()->format('H:i:s'),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'status' => 'checked_in',
                'is_active' => true,
                'daily_plan_store_id' => $request->daily_plan_store_id,
            ]);

            // If a plan store id was provided, mark it as visited
            if ($request->daily_plan_store_id) {
                DailyPlanStore::where('id', $request->daily_plan_store_id)
                    ->update(['status' => 'visited']);
            }

            DB::commit();

            $nearbyStores = $this->getNearbyAssignedStores(
                $employee,
                $request->latitude,
                $request->longitude,
                $visit->store_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Checked in successfully.',
                'data' => $visit,
                'nearby_stores' => $nearbyStores,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Check-in failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /visits/{id}/check-out
     */
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

            $target = EmployeeTarget::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'month' => now()->month,
                    'year' => now()->year,
                ],
                [
                    'visit_target' => 0,
                    'visits_completed' => 0,
                    'sales_target' => 0,
                    'sales_achieved' => 0,
                ]
            );

            $target->increment('visits_completed');
            $target->updateStatus();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Checked out successfully.',
                'data' => $visit->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Check-out failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /visits/today
     *
     * Returns current open (not yet checked-out) visit for today.
     */
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
            'data' => $visit,
        ]);
    }

    // ── Private helpers ──────────────────────────────────────────────

    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Returns stores within radius metres of a given lat/lng,
     * excluding the store just checked into.
     * Includes both assigned stores and stores in employee's state+city.
     */
    private function getNearbyAssignedStores(
        $employee,
        float $lat,
        float $lng,
        int $excludeStoreId,
        int $radiusMeters = 500
    ): array {
        $assignedIds = $employee->assignedStores()->pluck('stores.id');

        // All stores in same state+city as employee
        $areaStoreIds = Store::where('state_id', $employee->state_id)
            ->where('city_id', $employee->city_id)
            ->pluck('id');

        // Merge both sets
        $allStoreIds = $assignedIds->merge($areaStoreIds)->unique();

        if ($allStoreIds->isEmpty()) {
            return [];
        }

        $nearbyStores = Store::selectRaw("
            id,
            name,
            address,
            latitude,
            longitude,
            ROUND(
                6371000 * acos(
                    GREATEST(-1, LEAST(1,
                        cos(radians(?)) * cos(radians(latitude))
                        * cos(radians(longitude) - radians(?))
                        + sin(radians(?)) * sin(radians(latitude))
                    ))
                )
            ) AS distance_meters
        ", [$lat, $lng, $lat])
            ->whereIn('id', $allStoreIds)
            ->where('id', '!=', $excludeStoreId)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance_meters', '<=', $radiusMeters)
            ->orderBy('distance_meters')
            ->get();

        $todayPlan = DailyPlan::where('employee_id', $employee->id)
            ->whereDate('plan_date', today())
            ->with('planStores:id,daily_plan_id,store_id,status,visit_order')
            ->first();

        $planStoreIds = collect();
        if ($todayPlan) {
            $planStoreIds = $todayPlan->planStores->pluck('store_id');
        }

        return $nearbyStores->map(function ($store) use ($planStoreIds, $todayPlan, $assignedIds) {
            $planEntry = $todayPlan
                ? $todayPlan->planStores->firstWhere('store_id', $store->id)
                : null;

            return [
                'id' => $store->id,
                'name' => $store->name,
                'address' => $store->address,
                'latitude' => $store->latitude,
                'longitude' => $store->longitude,
                'distance_meters' => (int) $store->distance_meters,
                'is_assigned' => $assignedIds->contains($store->id),
                'in_today_plan' => $planStoreIds->contains($store->id),
                'daily_plan_store_id' => $planEntry?->id,
                'plan_visit_order' => $planEntry?->visit_order,
                'plan_status' => $planEntry?->status,
            ];
        })->toArray();
    }
}
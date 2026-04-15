<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyPlan;
use App\Models\DailyPlanStore;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiDailyPlanController extends Controller
{
    /**
     * GET /daily-plan/today
     *
     * Called right after login and on app home screen refresh.
     * Returns today's plan with each store's current status
     * and its linked visit (if already checked in).
     */
    public function today(Request $request)
    {
        $employee = $request->user()->employee;

        $plan = DailyPlan::where('employee_id', $employee->id)
            ->whereDate('plan_date', today())
            ->with([
                'planStores' => function ($q) {
                    $q->orderBy('visit_order')
                        ->with([
                            'store:id,name,address,latitude,longitude',
                            'visit:id,daily_plan_store_id,check_in_time,check_out_time,status,latitude,longitude',
                        ]);
                },
            ])
            ->first();

        if (!$plan) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No plan for today',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $plan->id,
                'plan_date' => $plan->plan_date->toDateString(),
                'notes' => $plan->notes,
                'manager_remark' => $plan->manager_remark,
                'day_started' => $plan->isDayStarted(),
                'day_ended' => $plan->isDayEnded(),
                'day_start_time' => $plan->day_start_time,
                'day_end_time' => $plan->day_end_time,
                'stores_planned' => $plan->planStores->count(),
                'stores_visited' => $plan->planStores->where('status', 'visited')->count(),
                'stores_pending' => $plan->planStores->where('status', 'pending')->count(),
                'plan_stores' => $plan->planStores->map(function ($ps) {
                    return [
                        'id' => $ps->id,
                        'visit_order' => $ps->visit_order,
                        'planned_time' => $ps->planned_time,
                        'status' => $ps->status,
                        'store' => [
                            'id' => $ps->store->id,
                            'name' => $ps->store->name,
                            'address' => $ps->store->address,
                            'latitude' => $ps->store->latitude,
                            'longitude' => $ps->store->longitude,
                        ],
                        'visit' => $ps->visit ? [
                            'id' => $ps->visit->id,
                            'check_in_time' => $ps->visit->check_in_time,
                            'check_out_time' => $ps->visit->check_out_time,
                            'status' => $ps->visit->status,
                            'latitude' => $ps->visit->latitude,
                            'longitude' => $ps->visit->longitude,
                        ] : null,
                    ];
                }),
            ],
        ]);
    }

    /**
     * POST /daily-plan
     *
     * Employee creates or updates today's plan.
     * Can be called multiple times — always replaces the store list
     * (only if day has NOT started yet, to prevent editing mid-day).
     *
     * Body:
     * {
     *   "plan_date": "2025-01-15",
     *   "notes": "Will start from north side",
     *   "stores": [
     *     { "store_id": 5, "visit_order": 1, "planned_time": "10:00" },
     *     { "store_id": 12, "visit_order": 2, "planned_time": "12:30" },
     *     { "store_id": 8, "visit_order": 3, "planned_time": null }
     *   ]
     * }
     */
    public function createOrUpdate(Request $request)
    {
        $request->validate([
            'plan_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'stores' => 'required|array|min:1',
            'stores.*.store_id' => 'required|integer|exists:stores,id',
            'stores.*.visit_order' => 'required|integer|min:1',
            'stores.*.planned_time' => 'nullable|date_format:H:i',
        ]);

        $employee = $request->user()->employee;

        // Verify all submitted stores are actually assigned to this employee
        $assignedStoreIds = $employee->assignedStores()->pluck('stores.id')->toArray();
        $submittedStoreIds = collect($request->stores)->pluck('store_id')->toArray();
        $unauthorised = array_diff($submittedStoreIds, $assignedStoreIds);

        if (!empty($unauthorised)) {
            return response()->json([
                'success' => false,
                'message' => 'One or more stores are not assigned to you.',
            ], 403);
        }

        // Check if day already started — block editing if so
        $existingPlan = DailyPlan::where('employee_id', $employee->id)
            ->whereDate('plan_date', $request->plan_date)
            ->first();

        if ($existingPlan && $existingPlan->day_start_time !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit the plan after the day has started.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $plan = DailyPlan::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'plan_date' => $request->plan_date,
                ],
                [
                    'notes' => $request->notes,
                    'is_active' => true,
                ]
            );

            // Delete old store list and rebuild — simple and clean
            $plan->planStores()->delete();

            foreach ($request->stores as $storeData) {
                DailyPlanStore::create([
                    'daily_plan_id' => $plan->id,
                    'store_id' => $storeData['store_id'],
                    'visit_order' => $storeData['visit_order'],
                    'planned_time' => $storeData['planned_time'] ?? null,
                    'status' => 'pending',
                ]);
            }

            DB::commit();

            // Reload with stores for response
            $plan->load([
                'planStores' => fn($q) => $q->orderBy('visit_order')->with('store:id,name,address'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Daily plan saved.',
                'data' => [
                    'id' => $plan->id,
                    'plan_date' => $plan->plan_date->toDateString(),
                    'notes' => $plan->notes,
                    'plan_stores' => $plan->planStores->map(fn($ps) => [
                        'id' => $ps->id,
                        'visit_order' => $ps->visit_order,
                        'planned_time' => $ps->planned_time,
                        'status' => $ps->status,
                        'store' => [
                            'id' => $ps->store->id,
                            'name' => $ps->store->name,
                            'address' => $ps->store->address,
                        ],
                    ]),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to save plan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /daily-plan/day-start
     *
     * Employee taps "Start Day" button.
     * Creates plan row if somehow it doesn't exist yet
     * (employee skipped plan creation and went straight to starting).
     *
     * Body: { "latitude": 23.0225, "longitude": 72.5714 }
     */
    public function dayStart(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $employee = $request->user()->employee;

        // Get or create today's plan
        $plan = DailyPlan::firstOrCreate(
            [
                'employee_id' => $employee->id,
                'plan_date' => today(),
            ],
            [
                'is_active' => true,
            ]
        );

        // Prevent double start
        if ($plan->day_start_time !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Day already started at ' . $plan->day_start_time,
            ], 422);
        }

        $plan->update([
            'day_start_time' => now()->format('H:i:s'),
            'start_lat' => $request->latitude,
            'start_lng' => $request->longitude,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Day started.',
            'data' => [
                'plan_id' => $plan->id,
                'day_start_time' => $plan->fresh()->day_start_time,
                'start_lat' => $plan->fresh()->start_lat,
                'start_lng' => $plan->fresh()->start_lng,
            ],
        ]);
    }

    /**
     * POST /daily-plan/day-end
     *
     * Employee taps "End Day" button.
     *
     * Body: { "latitude": 23.0225, "longitude": 72.5714 }
     */
    public function dayEnd(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $employee = $request->user()->employee;

        $plan = DailyPlan::where('employee_id', $employee->id)
            ->whereDate('plan_date', today())
            ->first();

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'No plan found for today. Start your day first.',
            ], 422);
        }

        if ($plan->day_start_time === null) {
            return response()->json([
                'success' => false,
                'message' => 'You have not started your day yet.',
            ], 422);
        }

        // Allow updating end time (employee may tap by mistake and redo)
        $plan->update([
            'day_end_time' => now()->format('H:i:s'),
            'end_lat' => $request->latitude,
            'end_lng' => $request->longitude,
        ]);

        // Mark any pending plan stores as skipped
        $plan->planStores()
            ->where('status', 'pending')
            ->update(['status' => 'skipped']);

        $plan->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Day ended.',
            'data' => [
                'plan_id' => $plan->id,
                'day_start_time' => $plan->day_start_time,
                'day_end_time' => $plan->day_end_time,
                'stores_planned' => $plan->planStores()->count(),
                'stores_visited' => $plan->planStores()->where('status', 'visited')->count(),
                'stores_skipped' => $plan->planStores()->where('status', 'skipped')->count(),
            ],
        ]);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyPlan;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\StoreVisit;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\StoreFlag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiStoreController extends Controller
{
    /**
     * GET /stores
     *
     * Returns:
     *  - All stores explicitly assigned to this employee
     *  - All stores in the same state_id + city_id as the employee (not yet assigned)
     *
     * Each store carries an `is_assigned` boolean so the app can
     * visually distinguish assigned vs area stores.
     */
public function getAssignedStores(Request $request)
{
    $employee = $request->user()->employee;

    // Assigned store IDs
    $assignedStoreIds = $employee->assignedStores()
        ->pluck('stores.id')
        ->toArray();

    $search = trim($request->input('search', ''));
    $perPage = max(5, min(100, (int) $request->input('per_page', 15)));

    // ── 1. Assigned Stores (Paginated) ────────────────────────
    $assignedQuery = Store::query()
        ->with(['state', 'city', 'area'])
        ->whereIn('id', $assignedStoreIds);

    if ($search) {
        $assignedQuery->where(function ($q) use ($search) {
            $q->where('stores.name', 'like', "%{$search}%")
                ->orWhere('stores.address', 'like', "%{$search}%")
                ->orWhere('stores.contact_number_1', 'like', "%{$search}%")
                ->orWhereHas('city', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                ->orWhereHas('area', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                ->orWhereHas('state', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
        });
    }

    $assignedPaginated = $assignedQuery->paginate($perPage, ['*'], 'page');

    // ── 2. Matching Stores (Fixed whereNotIn) ───────────────────
    $matchingQuery = Store::query()
        ->with(['state', 'city', 'area'])
        ->where('state_id', $employee->state_id)
        ->where('city_id', $employee->city_id);

    // Important Fix: whereNotIn ko safe banao
    if (!empty($assignedStoreIds)) {
        $matchingQuery->whereNotIn('id', $assignedStoreIds);
    }
    // Agar assignedStoreIds empty hai toh whereNotIn mat lagao (sab matching aayenge)

    if ($search) {
        $matchingQuery->where(function ($q) use ($search) {
            $q->where('stores.name', 'like', "%{$search}%")
                ->orWhere('stores.address', 'like', "%{$search}%")
                ->orWhere('stores.contact_number_1', 'like', "%{$search}%")
                ->orWhereHas('city', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                ->orWhereHas('area', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                ->orWhereHas('state', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
        });
    }

    $matchingStores = $matchingQuery->get();

    // ─ Common Data for All Stores ──────────────────────────
    $allStoreIds = $assignedPaginated->getCollection()
        ->pluck('id')
        ->merge($matchingStores->pluck('id'))
        ->unique();

    // Latest Visits
    $latestVisits = DB::table('store_visits')
        ->selectRaw('store_id, MAX(id) as visit_id')
        ->where('employee_id', $employee->id)
        ->whereIn('store_id', $allStoreIds)
        ->groupBy('store_id')
        ->pluck('visit_id', 'store_id');

    $visits = [];
    if ($latestVisits->isNotEmpty()) {
        $visits = StoreVisit::whereIn('id', $latestVisits->values()->all())
            ->select('id', 'store_id', 'status', 'visit_date')
            ->get()
            ->keyBy('store_id')
            ->all();
    }

    // Survey Summary
    $surveySummary = [];
    if ($latestVisits->isNotEmpty()) {
        $surveys = DB::table('question_answers')
            ->selectRaw('visit_id, admin_status, COUNT(*) as cnt')
            ->whereIn('visit_id', $latestVisits->values()->all())
            ->groupBy('visit_id', 'admin_status')
            ->get();
        foreach ($surveys as $row) {
            $surveySummary[$row->visit_id][$row->admin_status] = (int) $row->cnt;
        }
    }

    // Stock Summary
    $stockSummary = [];
    if ($latestVisits->isNotEmpty()) {
        $stocks = DB::table('stock_transactions')
            ->selectRaw('visit_id, status, COUNT(*) as cnt')
            ->whereIn('visit_id', $latestVisits->values()->all())
            ->groupBy('visit_id', 'status')
            ->get();
        foreach ($stocks as $row) {
            $stockSummary[$row->visit_id][$row->status] = (int) $row->cnt;
        }
    }

    // Today's Plan
    $planStoreMap = collect();
    $todayPlan = DailyPlan::where('employee_id', $employee->id)
        ->whereDate('plan_date', today())
        ->with('planStores:id,daily_plan_id,store_id,visit_order,status')
        ->first();

    if ($todayPlan) {
        $planStoreMap = $todayPlan->planStores->keyBy('store_id');
    }

    // Flagged Stores
    $flaggedStoreIds = StoreFlag::where('employee_id', $employee->id)
        ->where('is_resolved', false)
        ->pluck('store_id')
        ->flip()
        ->toArray();

    // Assigned Pivot
    $assignedPivotMap = $employee->assignedStores()
        ->select('stores.id')
        ->withPivot('assigned_date')
        ->get()
        ->keyBy('id');

    // ── Transform Function ──────────────────────────────────
    $transformStore = function ($store) use (
        $visits, $surveySummary, $stockSummary, $planStoreMap,
        $flaggedStoreIds, $assignedStoreIds, $assignedPivotMap
    ) {
        $isAssigned = in_array($store->id, $assignedStoreIds);
        $visit = $visits[$store->id] ?? null;
        $visitId = $visit?->id;

        // Survey State
        $surveys = $visitId && isset($surveySummary[$visitId]) ? $surveySummary[$visitId] : [];
        $surveyState = empty($surveys) ? 'none' : 'approved';
        if (isset($surveys['pending']) || isset($surveys['needs_review'])) {
            $surveyState = 'pending';
        } elseif (isset($surveys['rejected'])) {
            $surveyState = 'rejected';
        }

        // Stock State
        $stocks = $visitId && isset($stockSummary[$visitId]) ? $stockSummary[$visitId] : [];
        $stockState = empty($stocks) ? 'none' : 'approved';
        if (isset($stocks['pending'])) $stockState = 'pending';
        elseif (isset($stocks['rejected'])) $stockState = 'rejected';
        elseif (isset($stocks['returned'])) $stockState = 'returned';
        elseif (isset($stocks['delivered'])) $stockState = 'delivered';

        $planEntry = $planStoreMap->get($store->id);
        $assignedPivot = $isAssigned ? $assignedPivotMap->get($store->id) : null;

        return [
            'id' => $store->id,
            'name' => $store->name,
            'address' => $store->address,
            'state' => $store->state?->name,
            'city' => $store->city?->name,
            'area' => $store->area?->name,
            'contact' => $store->contact_number_1,
            'email' => $store->email,
            'latitude' => $store->latitude,
            'longitude' => $store->longitude,
            'is_assigned' => $isAssigned,
            'assigned_date' => $assignedPivot?->pivot?->assigned_date 
                ? \Carbon\Carbon::parse($assignedPivot->pivot->assigned_date)->format('Y-m-d') 
                : null,
            'visit_status' => $visit?->status,
            'last_visit_date' => $visit && $visit->visit_date 
                ? \Carbon\Carbon::parse($visit->visit_date)->format('Y-m-d') 
                : null,
            'stock_status' => $stockState,
            'stock_pending' => ($stockState === 'pending'),
            'in_today_plan' => $planEntry !== null,
            'plan_visit_order' => $planEntry?->visit_order,
            'plan_status' => $planEntry?->status,
            'daily_plan_store_id' => $planEntry?->id,
            'is_flagged' => isset($flaggedStoreIds[$store->id]),
            'flag_note' => null,
        ];
    };

    $assignedItems = $assignedPaginated->getCollection()->map($transformStore)->values();
    $matchingItems = $matchingStores->map($transformStore)->values();

    // Visit Counts (assigned only)
    $visitCountsRaw = DB::table('stores')
        ->selectRaw("
            COALESCE(
                (SELECT sv.status FROM store_visits sv 
                 WHERE sv.store_id = stores.id AND sv.employee_id = ? 
                 ORDER BY sv.id DESC LIMIT 1), 'none'
            ) AS status_group, COUNT(*) AS cnt
        ", [$employee->id])
        ->whereIn('stores.id', $assignedStoreIds ?: [0])   // safe empty case
        ->groupBy('status_group')
        ->pluck('cnt', 'status_group')
        ->toArray();

    $visit_counts = [
        'none'      => $visitCountsRaw['none'] ?? 0,
        'checked_in'=> $visitCountsRaw['checked_in'] ?? 0,
        'completed' => $visitCountsRaw['completed'] ?? 0,
        'cancelled' => $visitCountsRaw['cancelled'] ?? 0,
    ];

    return response()->json([
        'success' => true,
        'assigned' => $assignedItems,
        'matching' => $matchingItems,
        'visit_counts' => $visit_counts,
        'today_plan' => $todayPlan ? [
            'id' => $todayPlan->id,
            'day_started' => $todayPlan->isDayStarted(),
            'day_ended' => $todayPlan->isDayEnded(),
            'stores_planned' => $todayPlan->planStores->count(),
            'stores_visited' => $todayPlan->planStores->where('status', 'visited')->count(),
        ] : null,
        'pagination' => [
            'current_page'   => $assignedPaginated->currentPage(),
            'last_page'      => $assignedPaginated->lastPage(),
            'per_page'       => $assignedPaginated->perPage(),
            'total_assigned' => $assignedPaginated->total(),
            'total_matching' => $matchingStores->count(),
            'total_combined' => $assignedPaginated->total() + $matchingStores->count(),
        ],
    ]);
}
    // ────────────────────────────────────────────────────────

    public function getStoreDetails(Request $request, $id)
    {
        $employee = $request->user()->employee;

        // ─ Access check: assigned OR in same state+city ──────────────
        $isAssigned = $employee->assignedStores()->where('stores.id', $id)->exists();

        if ($isAssigned) {
            $store = $employee->assignedStores()->with([
                'state', 'city', 'area',
                'categoryOne', 'categoryTwo', 'categoryThree',
            ])->findOrFail($id);
        } else {
            $store = Store::where('id', $id)
                ->where('state_id', $employee->state_id)
                ->where('city_id', $employee->city_id)
                ->with(['state', 'city', 'area', 'categoryOne', 'categoryTwo', 'categoryThree'])
                ->firstOrFail();
        }

        $latestVisit = StoreVisit::where('store_id', $id)
            ->where('employee_id', $employee->id)
            ->orderByDesc('id')
            ->first();

        $pendingCount            = 0;
        $hasEditableTransactions = false;
        $canStillEdit            = false;

        if ($latestVisit) {
            $daysSinceVisit = now()->diffInDays($latestVisit->visit_date);
            if ($daysSinceVisit <= 7) {
                $pendingCount = StockTransaction::where('store_id', $id)
                    ->where('employee_id', $employee->id)
                    ->where('status', 'pending')
                    ->where('visit_id', $latestVisit->id)
                    ->count();
                $hasEditableTransactions = $pendingCount > 0;
                $canStillEdit            = true;
            }
        }

        // Today's plan entry for this store
        $planEntry = null;
        $todayPlan = DailyPlan::where('employee_id', $employee->id)
            ->whereDate('plan_date', today())
            ->with(['planStores' => fn($q) => $q->where('store_id', $id)])
            ->first();
        if ($todayPlan) {
            $planEntry = $todayPlan->planStores->first();
        }

        // Active flag
        $activeFlag = StoreFlag::where('employee_id', $employee->id)
            ->where('store_id', $id)
            ->where('is_resolved', false)
            ->latest()
            ->first();

        $isFirstVisit = !StoreProduct::where('store_id', $id)->exists();
        $isNewStore   = $store->created_by_employee_id !== null;

        // Last 10 visits
        $visitHistory = StoreVisit::where('store_id', $id)
            ->where('employee_id', $employee->id)
            ->orderByDesc('visit_date')
            ->orderByDesc('check_in_time')
            ->limit(10)
            ->get()
            ->map(function ($visit) {
                $duration = null;
                if ($visit->check_in_time && $visit->check_out_time) {
                    $duration = \Carbon\Carbon::parse($visit->check_in_time)
                        ->diffInMinutes(\Carbon\Carbon::parse($visit->check_out_time));
                }
                return [
                    'id'               => $visit->id,
                    'visit_date'       => $visit->visit_date->toDateString(),
                    'check_in_time'    => $visit->check_in_time,
                    'check_out_time'   => $visit->check_out_time,
                    'duration_minutes' => $duration,
                    'status'           => $visit->status,
                    'is_planned'       => $visit->daily_plan_store_id !== null,
                    'visit_summary'    => $visit->visit_summary,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => [
                'id'               => $store->id,
                'name'             => $store->name,
                'address'          => $store->address,
                'state'            => $store->state?->name,
                'city'             => $store->city?->name,
                'area'             => $store->area?->name,
                'pin_code'         => $store->pin_code,
                'latitude'         => $store->latitude,
                'longitude'        => $store->longitude,
                'contact_number_1' => $store->contact_number_1,
                'contact_number_2' => $store->contact_number_2,
                'email'            => $store->email,

                // Assignment
                'is_assigned'      => $isAssigned,

                // Visit status
                'status'                 => $latestVisit?->status ?? null,
                'latest_visit_status'    => $latestVisit?->status ?? 'never_visited',
                'latest_visit_date'      => $latestVisit?->visit_date?->toDateString(),
                'latest_visit_id'        => $latestVisit?->id,
                'has_pending_stock'      => $hasEditableTransactions,
                'pending_stock_count'    => $pendingCount,
                'can_edit_stock'         => $canStillEdit,

                // Stock screen flags
                'is_first_visit'         => $isFirstVisit,
                'is_new_store'           => $isNewStore,

                // Plan info
                'in_today_plan'          => $planEntry !== null,
                'daily_plan_store_id'    => $planEntry?->id,
                'plan_visit_order'       => $planEntry?->visit_order,
                'plan_status'            => $planEntry?->status,

                // Visit history
                'visit_history'          => $visitHistory,
                'total_visits'           => StoreVisit::where('store_id', $id)
                    ->where('employee_id', $employee->id)
                    ->count(),

                // Flag info
                'is_flagged'             => $activeFlag !== null,
                'flag_id'                => $activeFlag?->id,
                'flag_note'              => $activeFlag?->flag_note,
                'flagged_at'             => $activeFlag?->created_at?->toDateTimeString(),
            ],
        ]);
    }

    /**
     * GET /stores/{id}/products
     */
    public function getStoreProducts(Request $request, $id)
    {
        $employee = $request->user()->employee;

        // Access check: assigned OR same state+city
        $isAssigned = $employee->assignedStores()->where('stores.id', $id)->exists();

        if ($isAssigned) {
            $store = $employee->assignedStores()->findOrFail($id);
        } else {
            $store = Store::where('id', $id)
                ->where('state_id', $employee->state_id)
                ->where('city_id', $employee->city_id)
                ->firstOrFail();
        }

        $latestStoreVisitId = $store->visits()
            ->where('employee_id', $employee->id)
            ->latest('visit_date')
            ->value('id');

        if (!$latestStoreVisitId) {
            return response()->json([
                'success'        => true,
                'is_first_visit' => true,
                'is_new_store'   => $store->created_by_employee_id !== null,
                'data'           => [],
                'message'        => 'Please check in to this store first.',
            ]);
        }

        $products = Product::where('is_active', true)
            ->where('state_id', $store->state_id)
            ->get();

        $storeProductsMap = StoreProduct::where('store_id', $id)
            ->get()
            ->keyBy('product_id');

        $pendingData = StockTransaction::where('store_id', $id)
            ->where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->selectRaw('product_id, type, COUNT(*) as count')
            ->groupBy('product_id', 'type')
            ->get()
            ->groupBy('product_id');

        $lastTransactionDates = StockTransaction::where('store_id', $id)
            ->where('employee_id', $employee->id)
            ->selectRaw('product_id, MAX(created_at) as last_transaction_at')
            ->groupBy('product_id')
            ->pluck('last_transaction_at', 'product_id')
            ->toArray();

        $isFirstVisit = !StoreProduct::where('store_id', $id)->exists();
        $isNewStore   = $store->created_by_employee_id !== null;

        return response()->json([
            'success'        => true,
            'is_first_visit' => $isFirstVisit,
            'is_new_store'   => $isNewStore,
            'data'           => $products->map(function ($product) use (
                $storeProductsMap,
                $pendingData,
                $lastTransactionDates,
                $latestStoreVisitId
            ) {
                $productId    = $product->id;
                $sp           = $storeProductsMap->get($productId);
                $pendingByType = $pendingData->get($productId, collect());
                $pendingAdd   = $pendingByType->firstWhere('type', 'add')?->count ?? 0;
                $pendingReturn = $pendingByType->firstWhere('type', 'return')?->count ?? 0;

                return [
                    'id'                   => $sp?->id,
                    'product_id'           => $productId,
                    'product_name'         => $product->name,
                    'mrp'                  => $product->mrp,
                    'sku'                  => $product->sku,
                    'pack_size'            => $product->pack_size,
                    'volume'               => $product->volume,
                    'image_url'            => $product->image
                        ? asset('storage/' . $product->image)
                        : null,
                    'current_stock'        => $sp?->current_stock ?? 0,
                    'pending_stock'        => $sp?->pending_stock ?? 0,
                    'return_stock'         => $sp?->return_stock ?? 0,
                    'available_stock'      => $sp ? $sp->available_stock : 0,
                    'product_total_stock'  => $product->total_stock ?? 0,
                    'has_pending'          => ($pendingAdd + $pendingReturn) > 0,
                    'pending_add_count'    => $pendingAdd,
                    'pending_return_count' => $pendingReturn,
                    'last_transaction_at'  => $lastTransactionDates[$productId] ?? null,
                    'visit_id'             => $latestStoreVisitId,
                ];
            }),
        ]);
    }

    /**
     * POST /stores/{id}/stock-count
     */
    public function saveStockCount(Request $request, $id)
    {
        $request->validate([
            'items'                   => 'required|array|min:1',
            'items.*.product_id'      => 'required|exists:products,id',
            'items.*.current_stock'   => 'required|integer|min:0',
        ]);

        $employee = $request->user()->employee;

        $isAssigned = $employee->assignedStores()->where('stores.id', $id)->exists();

        if ($isAssigned) {
            $store = $employee->assignedStores()->findOrFail($id);
        } else {
            $store = Store::where('id', $id)
                ->where('state_id', $employee->state_id)
                ->where('city_id', $employee->city_id)
                ->firstOrFail();
        }

        try {
            DB::beginTransaction();

            foreach ($request->items as $item) {
                $product = Product::where('id', $item['product_id'])
                    ->where('state_id', $store->state_id)
                    ->where('is_active', true)
                    ->first();

                if (!$product) continue;

                StoreProduct::updateOrCreate(
                    ['store_id' => $id, 'product_id' => $item['product_id']],
                    ['current_stock' => $item['current_stock'], 'pending_stock' => 0, 'return_stock' => 0]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock count saved successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to save stock count: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /stores/nearby
     *
     * Returns:
     *  Section A  ALL of today's plan stores (regardless of distance)
     *  Section B — Nearby stores within radius that are NOT in today's plan,
     *              including both assigned and same state+city stores
     */
public function getNearby(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius'    => 'nullable|integer|min:100',
        ]);

        $employee = $request->user()->employee;
        $lat      = (float) $request->latitude;
        $lng      = (float) $request->longitude;
        // $radius   = (int) $request->input('radius', 6371000);
        $radius = 6371000;

        // All store IDs the employee can access (assigned + same state+city)
        $assignedIds = $employee->assignedStores()->pluck('stores.id');

        $areaIds = Store::where('state_id', $employee->state_id)
            ->where('city_id', $employee->city_id)
            ->pluck('id');

        $allAccessibleIds = $assignedIds->merge($areaIds)->unique();

        if ($allAccessibleIds->isEmpty()) {
            return response()->json([
                'success'       => true,
                'plan_stores'   => [],
                'nearby_stores' => [],
                'your_location' => ['latitude' => $lat, 'longitude' => $lng],
            ]);
        }

        // ── Today's plan ──────────────────────────────────────────
        $todayPlan = DailyPlan::where('employee_id', $employee->id)
            ->whereDate('plan_date', today())
            ->with(['planStores.store'])
            ->first();

        $visitedTodayIds = StoreVisit::where('employee_id', $employee->id)
            ->whereDate('visit_date', today())
            ->pluck('store_id')
            ->toArray();

        $planStores   = [];
        $planStoreIds = collect();

        if ($todayPlan) {
            $planStoreIds = $todayPlan->planStores->pluck('store_id');

            $planStores = $todayPlan->planStores
                ->sortBy('visit_order')
                ->map(function ($planEntry) use ($visitedTodayIds, $lat, $lng, $assignedIds) {
                    $store    = $planEntry->store;
                    $distance = null;

                    if ($store->latitude && $store->longitude) {
                        $R    = 6371000;
                        $lat1 = deg2rad($lat);
                        $lat2 = deg2rad((float) $store->latitude);
                        $dLng = deg2rad((float) $store->longitude - $lng);
                        $distance = (int) round($R * acos(
                            max(-1, min(
                                1,
                                cos($lat1) * cos($lat2) * cos($dLng)
                                + sin($lat1) * sin($lat2)
                            ))
                        ));
                    }

                    return [
                        'id'                  => $store->id,
                        'name'                => $store->name,
                        'address'             => $store->address,
                        'latitude'            => $store->latitude ? (float) $store->latitude : null,
                        'longitude'           => $store->longitude ? (float) $store->longitude : null,
                        'distance_meters'     => $distance,
                        'is_assigned'         => $assignedIds->contains($store->id),
                        'in_today_plan'       => true,
                        'daily_plan_store_id' => $planEntry->id,
                        'plan_visit_order'    => $planEntry->visit_order,
                        'plan_status'         => $planEntry->status,
                        'visited_today'       => in_array($store->id, $visitedTodayIds),
                        'planned_time'        => $planEntry->planned_time,
                    ];
                })
                ->values()
                ->toArray();
        }

        // ─ Nearby non-plan stores ───────────────────────────────
        $nearbyStores = Store::selectRaw("
            id,
            name,
            address,
            latitude,
            longitude,
            state_id,
            city_id,
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
            ->whereIn('id', $allAccessibleIds)
            ->whereNotIn('id', $planStoreIds->isEmpty() ? [0] : $planStoreIds->toArray())
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance_meters', '<=', $radius)
            ->orderBy('distance_meters')
            ->get()
            ->map(function ($store) use ($visitedTodayIds, $assignedIds) {
                return [
                    'id'                  => $store->id,
                    'name'                => $store->name,
                    'address'             => $store->address,
                    'latitude'            => (float) $store->latitude,
                    'longitude'           => (float) $store->longitude,
                    'distance_meters'     => (int) $store->distance_meters,
                    'is_assigned'         => $assignedIds->contains($store->id),
                    'in_today_plan'       => false,
                    'daily_plan_store_id' => null,
                    'plan_visit_order'    => null,
                    'plan_status'         => null,
                    'visited_today'       => in_array($store->id, $visitedTodayIds),
                    'planned_time'        => null,
                ];
            })
            ->toArray();

        return response()->json([
            'success'        => true,
            'plan_stores'    => $planStores,
            'nearby_stores'  => $nearbyStores,
            'your_location'  => ['latitude' => $lat, 'longitude' => $lng],
            'radius_meters'  => $radius,
            'today_plan_id'  => $todayPlan?->id,
            'day_started'    => $todayPlan?->day_start_time !== null,
            'day_ended'      => $todayPlan?->day_end_time !== null,
        ]);
    }
}
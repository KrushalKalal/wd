<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\Employee;
use App\Models\StoreVisit;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiStoreController extends Controller
{
    public function getAssignedStores(Request $request)
    {
        $employee = $request->user()->employee;

        $query = $employee->assignedStores()
            ->select('stores.*')
            ->with(['state', 'city', 'area']);

        // Search filter
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('stores.name', 'like', "%{$search}%")
                    ->orWhere('stores.address', 'like', "%{$search}%")
                    ->orWhere('stores.contact_number_1', 'like', "%{$search}%")
                    ->orWhereHas('city', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('area', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('state', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        // Pagination
        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(5, min(100, $perPage)); // reasonable guard

        $paginated = $query->paginate($perPage);
        $storeIds = $paginated->pluck('id');

        // ──────────────────────────────────────────────────────
        // 1. Latest visit per store (only this employee's visits)
        // ──────────────────────────────────────────────────────
        $latestVisits = DB::table('store_visits')
            ->selectRaw('store_id, MAX(id) as visit_id')
            ->where('employee_id', $employee->id)
            ->whereIn('store_id', $storeIds)
            ->groupBy('store_id')
            ->pluck('visit_id', 'store_id');

        $visits = [];
        if ($latestVisits->isNotEmpty()) {
            $visits = StoreVisit::query()
                ->whereIn('id', $latestVisits->values()->all())
                ->select('id', 'store_id', 'status', 'visit_date')
                ->get()
                ->keyBy('store_id')
                ->all();
        }

        // ───────────────────────────────────────────────────────
        // 2. Survey summary per latest visit
        // ───────────────────────────────────────────────────────
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

        // ───────────────────────────────────────────────────────
        // 3. Stock summary per latest visit
        // ──────────────────────────────────────────────────────
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

        // ───────────────────────────────────────────────────────
        // Transform response items
        // ──────────────────────────────────────────────────────
        $items = $paginated->through(function ($store) use ($visits, $surveySummary, $stockSummary) {
            $visit = $visits[$store->id] ?? null;
            $visitId = $visit ? $visit->id : null;

            // Visit status (null if never visited by this employee)
            $visitStatus = $visit ? $visit->status : null;

            // Survey summary for latest visit
            $surveys = $visitId && isset($surveySummary[$visitId]) ? $surveySummary[$visitId] : [];
            $surveyState = empty($surveys) ? 'none' : 'approved';

            if (isset($surveys['pending']) || isset($surveys['needs_review'])) {
                $surveyState = 'pending';
            } elseif (isset($surveys['rejected'])) {
                $surveyState = 'rejected';
            }

            // Stock summary for latest visit
            $stocks = $visitId && isset($stockSummary[$visitId]) ? $stockSummary[$visitId] : [];
            $stockState = empty($stocks) ? 'none' : 'approved';

            if (isset($stocks['pending'])) {
                $stockState = 'pending';
            } elseif (isset($stocks['rejected'])) {
                $stockState = 'rejected';
            } elseif (isset($stocks['returned'])) {
                $stockState = 'returned';
            } elseif (isset($stocks['delivered'])) {
                $stockState = 'delivered';
            }

            return [
                'id' => $store->id,
                'name' => $store->name,
                'address' => $store->address,
                'state' => $store->state?->name,
                'city' => $store->city?->name,
                'area' => $store->area?->name,
                'contact' => $store->contact_number_1,
                'email' => $store->email,
                'assigned_date' => $store->pivot->assigned_date
                    ? \Carbon\Carbon::parse($store->pivot->assigned_date)->format('Y-m-d')
                    : null,
                'visit_status' => $visitStatus,           // null | checked_in | completed | cancelled
                'last_visit_date' => $visit && $visit->visit_date
                    ? \Carbon\Carbon::parse($visit->visit_date)->format('Y-m-d')
                    : null,

                'stock_status' => $stockState,            // none | pending | rejected | returned | delivered | approved
                'stock_pending' => ($stockState === 'pending'),
            ];
        });

        // ──────────────────────────────────────────────────────
        // Global visit status counts (across ALL assigned stores)
        // ───────────────────────────────────────────────────────
        $visitCountsRaw = DB::table('stores')
            ->selectRaw("
            COALESCE(
                (SELECT sv.status
                 FROM store_visits sv
                 WHERE sv.store_id = stores.id
                   AND sv.employee_id = ?
                 ORDER BY sv.id DESC
                 LIMIT 1),
                'none'
            ) AS status_group,
            COUNT(*) AS cnt
        ", [$employee->id])
            ->whereIn('stores.id', $employee->assignedStores()->select('stores.id'))
            ->groupBy('status_group')
            ->pluck('cnt', 'status_group')
            ->toArray();

        $visit_counts = [
            'none' => $visitCountsRaw['none'] ?? 0,
            'checked_in' => $visitCountsRaw['checked_in'] ?? 0,
            'completed' => $visitCountsRaw['completed'] ?? 0,
            'cancelled' => $visitCountsRaw['cancelled'] ?? 0,
        ];

        return response()->json([
            'success' => true,
            'data' => $items,
            'visit_counts' => $visit_counts,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function getStoreDetails(Request $request, $id)
    {
        $employee = $request->user()->employee;

        $store = $employee->assignedStores()->with([
            'state',
            'city',
            'area',
            'categoryOne',
            'categoryTwo',
            'categoryThree',
            'visits' => function ($q) {
                $q->latest('visit_date')->take(3); // last few visits for safety
            }
        ])->findOrFail($id);

        $latestVisit = StoreVisit::where('store_id', $id)
            ->where('employee_id', $employee->id)
            ->orderByDesc('id')
            ->first();

        // ─ Pending stock check (for future edit/correct feature) ─
        $pendingCount = 0;
        $hasEditableTransactions = false;
        $canStillEdit = false;

        if ($latestVisit) {
            $daysSinceVisit = now()->diffInDays($latestVisit->visit_date);

            // Example policy: allow edit/correction up to 7 days after visit
            if ($daysSinceVisit <= 7) {
                $pendingCount = StockTransaction::where('store_id', $id)
                    ->where('employee_id', $employee->id)
                    ->where('status', 'pending')
                    ->where('visit_id', $latestVisit->id)           // ← most apps limit to current/latest visit
                    // or remove where('visit_id', ...) if you allow cross-visit corrections
                    ->count();

                $hasEditableTransactions = $pendingCount > 0;
                $canStillEdit = true; // or $hasEditableTransactions || true (allow new additions too)
            }
        }

        $data = [
            'id' => $store->id,
            'name' => $store->name,
            'address' => $store->address,
            'state' => $store->state?->name,
            'city' => $store->city?->name,
            'area' => $store->area?->name,
            'pin_code' => $store->pin_code,
            'latitude' => $store->latitude,
            'longitude' => $store->longitude,
            'contact_number_1' => $store->contact_number_1,
            'contact_number_2' => $store->contact_number_2,
            'email' => $store->email,
            'billing_details' => $store->billing_details,
            'shipping_details' => $store->shipping_details,

            // ── KEEP THIS EXACTLY AS ORIGINAL so frontend doesn't break ──
            'status' => $latestVisit?->status ?? null,   // null / 'never_visited' / 'checked_in' / 'completed'

            // ─ New helpful fields (for future features) ──
            'latest_visit_status' => $latestVisit?->status ?? 'never_visited',
            'latest_visit_date' => $latestVisit?->visit_date?->toDateString(),
            'latest_visit_id' => $latestVisit?->id,
            'has_pending_stock' => $hasEditableTransactions,
            'pending_stock_count' => $pendingCount,
            'can_edit_stock' => $canStillEdit,           // or $hasEditableTransactions

        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function getStoreProducts(Request $request, $id)
    {
        $employee = $request->user()->employee;

        $store = $employee->assignedStores()->findOrFail($id);

        // Latest visit to this store (any status) — useful for posting new transactions
        $latestStoreVisitId = $store->visits()
            ->where('employee_id', $employee->id)
            ->latest('visit_date')           // or latest('created_at')
            ->value('id');

        $products = StoreProduct::where('store_id', $id)->get();

        // Pending counts per product + type
        $pendingData = StockTransaction::where('store_id', $id)
            ->where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->selectRaw('product_id, type, COUNT(*) as count')
            ->groupBy('product_id', 'type')
            ->get()
            ->groupBy('product_id');

        // Latest pending transaction's visit_id per product
        $latestPendingVisits = StockTransaction::where('store_id', $id)
            ->where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->select('product_id', 'visit_id')
            ->whereIn('created_at', function ($sub) use ($id, $employee) {
                $sub->selectRaw('MAX(created_at)')
                    ->from('stock_transactions')
                    ->whereColumn('product_id', 'stock_transactions.product_id')
                    ->where('store_id', $id)
                    ->where('employee_id', $employee->id)
                    ->where('status', 'pending')
                    ->groupBy('product_id');
            })
            ->get()
            ->pluck('visit_id', 'product_id')
            ->toArray();

        // Optional: last transaction date overall per product
        $lastTransactionDates = StockTransaction::where('store_id', $id)
            ->where('employee_id', $employee->id)
            ->selectRaw('product_id, MAX(created_at) as last_transaction_at')
            ->groupBy('product_id')
            ->pluck('last_transaction_at', 'product_id')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => $products->map(function ($sp) use ($pendingData, $latestPendingVisits, $lastTransactionDates, $latestStoreVisitId) {
                $productId = $sp->product_id;

                $pendingByType = $pendingData->get($productId, collect());
                $pendingAdd = $pendingByType->firstWhere('type', 'add')?->count ?? 0;
                $pendingReturn = $pendingByType->firstWhere('type', 'return')?->count ?? 0;
                $totalPending = $pendingAdd + $pendingReturn;

                return [
                    'id' => $sp->id,
                    'product_id' => $sp->product_id,
                    'product_name' => $sp->product->name,
                    'mrp' => $sp->product->mrp,
                    'sku' => $sp->product->sku,
                    'pack_size' => $sp->product->pack_size,
                    'volume' => $sp->product->volume,
                    'image_url' => $sp->product->image
                        ? asset('storage/' . $sp->product->image)
                        : null,
                    'current_stock' => $sp->current_stock,
                    'pending_stock' => $sp->pending_stock,
                    'return_stock' => $sp->return_stock,
                    'available_stock' => $sp->available_stock,

                    // 'category_one' => $sp->product->categoryOne?->name,
                    // 'category_two' => $sp->product->categoryTwo?->name,
                    // 'category_three' => $sp->product->categoryThree?->name,
    
                    // Pending info (only meaningful when pending exists)
                    'has_pending' => $totalPending > 0,
                    'pending_add_count' => $pendingAdd,
                    'pending_return_count' => $pendingReturn,
                    'latest_pending_visit_id' => $totalPending > 0 ? ($latestPendingVisits[$productId] ?? null) : null,

                    // Last any transaction (optional)
                    'last_transaction_at' => $lastTransactionDates[$productId] ?? null,

                    // Most recent visit to the store — use this to POST new stock entries
                    'visit_id' => $latestStoreVisitId,
                ];
            })
        ]);
    }
}
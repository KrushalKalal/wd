<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreVisit;
use App\Models\Employee;
use App\Models\Product;
use App\Models\State;
use App\Models\QuestionAnswer;
use App\Models\StockTransaction;
use App\Helpers\RoleAccessHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class StoreManagementController extends Controller
{
    /**
     * Display unified store management dashboard
     */
    public function index(Request $request)
    {
        $query = Store::with([
            'state.zone',
            'city',
            'area',
            'visits' => function ($q) {
                $q->latest()->limit(1);
            },
            'visits.employee.user',
            'visits.questionAnswers.question',
            'visits.stockTransactions.product'
        ]);

        // Apply role-based filter
        $query = RoleAccessHelper::applyRoleFilter($query);

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('contact_number_1', 'like', '%' . $request->search . '%')
                    ->orWhereHas('city', function ($cityQuery) use ($request) {
                        $cityQuery->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        // Filter by state
        if ($request->has('state_id') && $request->state_id) {
            $query->where('state_id', $request->state_id);
        }

        // Filter by city
        if ($request->has('city_id') && $request->city_id) {
            $query->where('city_id', $request->city_id);
        }

        // Filter by visit status
        if ($request->has('visit_status') && $request->visit_status !== 'all') {
            $query->whereHas('visits', function ($q) use ($request) {
                $q->where('status', $request->visit_status);
            });
        }

        $perPage = $request->get('per_page', 15);
        $stores = $query->orderBy('name')->paginate($perPage);

        // Transform data to include aggregated stats
        $stores->getCollection()->transform(function ($store) {
            $latestVisit = $store->visits->first();

            $store->latest_visit = $latestVisit ? [
                'id' => $latestVisit->id,
                'date' => $latestVisit->visit_date,
                'status' => $latestVisit->status,
                'employee_name' => $latestVisit->employee->name,
                'check_in_time' => $latestVisit->check_in_time,
                'check_out_time' => $latestVisit->check_out_time,
            ] : null;

            // Survey stats
            if ($latestVisit) {
                $surveyAnswers = $latestVisit->questionAnswers;
                $store->survey_stats = [
                    'total' => $surveyAnswers->count(),
                    'pending' => $surveyAnswers->where('admin_status', 'pending')->count(),
                    'approved' => $surveyAnswers->where('admin_status', 'approved')->count(),
                    'rejected' => $surveyAnswers->where('admin_status', 'rejected')->count(),
                    'needs_review' => $surveyAnswers->where('admin_status', 'needs_review')->count(),
                ];

                // Stock stats
                $stockTransactions = $latestVisit->stockTransactions;
                $store->stock_stats = [
                    'total' => $stockTransactions->count(),
                    'pending' => $stockTransactions->where('status', 'pending')->count(),
                    'approved' => $stockTransactions->where('status', 'approved')->count(),
                    'delivered' => $stockTransactions->where('status', 'delivered')->count(),
                    'returned' => $stockTransactions->where('status', 'returned')->count(),
                    'rejected' => $stockTransactions->where('status', 'rejected')->count(),
                ];
            } else {
                $store->survey_stats = [
                    'total' => 0,
                    'pending' => 0,
                    'approved' => 0,
                    'rejected' => 0,
                    'needs_review' => 0,
                ];
                $store->stock_stats = [
                    'total' => 0,
                    'pending' => 0,
                    'approved' => 0,
                    'delivered' => 0,
                    'returned' => 0,
                    'rejected' => 0,
                ];
            }

            return $store;
        });

        // Get accessible states for filter
        $stateIds = RoleAccessHelper::getAccessibleStateIds();
        $states = State::whereIn('id', $stateIds)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        // Get overall statistics
        $statistics = [
            'total_stores' => $query->count(),
            'visited_today' => StoreVisit::whereDate('visit_date', today())->count(),
            'pending_surveys' => QuestionAnswer::where('admin_status', 'pending')->count(),
            'pending_stock' => StockTransaction::where('status', 'pending')->count(),
        ];

        return Inertia::render('StoreManagement/Index', [
            'records' => $stores,
            'states' => $states,
            'statistics' => $statistics,
            'filters' => [
                'search' => $request->search,
                'state_id' => $request->state_id,
                'city_id' => $request->city_id,
                'visit_status' => $request->visit_status ?? 'all',
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Show detailed store management page
     */
    public function show($storeId)
    {
        $store = Store::with([
            'state.zone',
            'city',
            'area',
            'categoryOne',
            'categoryTwo',
            'categoryThree',
        ])->findOrFail($storeId);

        // Get all visits for this store
        $visits = StoreVisit::where('store_id', $storeId)
            ->with([
                'employee.user',
                'employee.manager',
                'questionAnswers.question',
                'questionAnswers.reviewer',
                'stockTransactions.product',
                'stockTransactions.approvedBy'
            ])
            ->orderBy('visit_date', 'desc')
            ->orderBy('check_in_time', 'desc')
            ->get();

        // Transform visits with detailed stats
        $visits->transform(function ($visit) {
            $visit->duration_minutes = null;
            if ($visit->check_in_time && $visit->check_out_time) {
                $checkIn = \Carbon\Carbon::parse($visit->check_in_time);
                $checkOut = \Carbon\Carbon::parse($visit->check_out_time);
                $visit->duration_minutes = $checkIn->diffInMinutes($checkOut);
            }

            return $visit;
        });

        return Inertia::render('StoreManagement/Details', [
            'store' => $store,
            'visits' => $visits,
        ]);
    }

    /**
     * Bulk approve all pending surveys for a visit
     */
    public function bulkApproveSurveys(Request $request, $visitId)
    {
        $request->validate([
            'admin_remark' => 'nullable|string|max:500',
        ]);

        try {
            $count = QuestionAnswer::where('visit_id', $visitId)
                ->where('admin_status', 'pending')
                ->update([
                    'admin_status' => 'approved',
                    'admin_remark' => $request->admin_remark,
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => "{$count} survey answers approved successfully"
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk survey approval failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve surveys'
            ], 500);
        }
    }

    /**
     * Review individual survey answer
     */
    public function reviewSurveyAnswer(Request $request, $answerId)
    {
        $request->validate([
            'admin_status' => 'required|in:approved,rejected,needs_review',
            'admin_remark' => 'nullable|string|max:500',
        ]);

        try {
            $answer = QuestionAnswer::findOrFail($answerId);
            $answer->update([
                'admin_status' => $request->admin_status,
                'admin_remark' => $request->admin_remark,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Survey answer reviewed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Survey review failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to review survey answer'
            ], 500);
        }
    }

    /**
     * Approve stock transaction
     */
    public function approveStock(Request $request, $transactionId)
    {
        $request->validate([
            'admin_remark' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $transaction = StockTransaction::findOrFail($transactionId);

            if ($transaction->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending transactions can be approved'
                ], 400);
            }

            $transaction->update([
                'status' => 'approved',
                'admin_remark' => $request->admin_remark,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction approved successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stock approval failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve transaction'
            ], 500);
        }
    }

    /**
     * Reject stock transaction
     */
    public function rejectStock(Request $request, $transactionId)
    {
        $request->validate([
            'admin_remark' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $transaction = StockTransaction::findOrFail($transactionId);

            if ($transaction->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending transactions can be rejected'
                ], 400);
            }

            $transaction->update([
                'status' => 'rejected',
                'admin_remark' => $request->admin_remark,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Remove from pending/return stock
            $storeProduct = \App\Models\StoreProduct::where('store_id', $transaction->store_id)
                ->where('product_id', $transaction->product_id)
                ->first();

            if ($storeProduct) {
                if ($transaction->type === 'add') {
                    $storeProduct->decrement('pending_stock', $transaction->quantity);
                } else {
                    $storeProduct->decrement('return_stock', $transaction->quantity);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction rejected successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stock rejection failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject transaction'
            ], 500);
        }
    }

    /**
     * Mark stock as delivered
     */
    public function markStockDelivered(Request $request, $transactionId)
    {
        $request->validate([
            'admin_remark' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $transaction = StockTransaction::findOrFail($transactionId);

            if ($transaction->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only approved transactions can be delivered'
                ], 400);
            }

            if ($transaction->type !== 'add') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only ADD type transactions can be marked as delivered'
                ], 400);
            }

            $transaction->update([
                'status' => 'delivered',
                'admin_remark' => $request->admin_remark ?? $transaction->admin_remark,
            ]);

            // Update stock
            $storeProduct = \App\Models\StoreProduct::firstOrCreate(
                [
                    'store_id' => $transaction->store_id,
                    'product_id' => $transaction->product_id,
                ],
                [
                    'current_stock' => 0,
                    'pending_stock' => 0,
                    'return_stock' => 0,
                ]
            );

            $storeProduct->increment('current_stock', $transaction->quantity);
            $storeProduct->decrement('pending_stock', $transaction->quantity);

            // Update product total stock - DECREMENT because stock left warehouse
            $product = Product::findOrFail($transaction->product_id);
            $product->decrement('total_stock', $transaction->quantity);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Stock delivered successfully. Added {$transaction->quantity} units to store.",
                'data' => [
                    'new_current_stock' => $storeProduct->current_stock,
                    'new_pending_stock' => $storeProduct->pending_stock,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mark delivered failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark as delivered'
            ], 500);
        }
    }

    /**
     * Mark stock as returned
     */
    public function markStockReturned(Request $request, $transactionId)
    {
        $request->validate([
            'admin_remark' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $transaction = StockTransaction::findOrFail($transactionId);

            if ($transaction->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only approved transactions can be marked as returned'
                ], 400);
            }

            if ($transaction->type !== 'return') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only RETURN type transactions can be marked as returned'
                ], 400);
            }

            $storeProduct = \App\Models\StoreProduct::where('store_id', $transaction->store_id)
                ->where('product_id', $transaction->product_id)
                ->first();

            if (!$storeProduct) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store product not found'
                ], 404);
            }

            if ($storeProduct->current_stock < $transaction->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient stock. Current: {$storeProduct->current_stock}, Return qty: {$transaction->quantity}"
                ], 400);
            }

            $transaction->update([
                'status' => 'returned',
                'admin_remark' => $request->admin_remark ?? $transaction->admin_remark,
            ]);

            $storeProduct->decrement('current_stock', $transaction->quantity);
            $storeProduct->decrement('return_stock', $transaction->quantity);

            // Update product total stock - INCREMENT because stock came back to warehouse
            $product = Product::findOrFail($transaction->product_id);
            $product->increment('total_stock', $transaction->quantity);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Stock returned successfully. Removed {$transaction->quantity} units from store.",
                'data' => [
                    'new_current_stock' => $storeProduct->current_stock,
                    'new_return_stock' => $storeProduct->return_stock,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mark returned failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark as returned'
            ], 500);
        }
    }

    /**
     * Bulk approve all pending items for a visit
     */
    public function bulkApproveVisit(Request $request, $visitId)
    {
        $request->validate([
            'admin_remark' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Approve all pending surveys
            $surveyCount = QuestionAnswer::where('visit_id', $visitId)
                ->where('admin_status', 'pending')
                ->update([
                    'admin_status' => 'approved',
                    'admin_remark' => $request->admin_remark,
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                ]);

            // Approve all pending stock transactions
            $stockCount = StockTransaction::where('visit_id', $visitId)
                ->where('status', 'pending')
                ->update([
                    'status' => 'approved',
                    'admin_remark' => $request->admin_remark,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Approved {$surveyCount} surveys and {$stockCount} stock transactions"
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk approval failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk approve'
            ], 500);
        }
    }
}
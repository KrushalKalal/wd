<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreVisit;
use App\Models\Employee;
use App\Models\Product;
use App\Models\State;
use App\Models\QuestionAnswer;
use App\Models\StockTransaction;
use App\Models\Order;
use App\Helpers\RoleAccessHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class StoreManagementController extends Controller
{
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
            'visits.stockTransactions.product',
            'visits.orders'
        ]);

        $query = RoleAccessHelper::applyRoleFilter($query);

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('contact_number_1', 'like', '%' . $request->search . '%')
                    ->orWhereHas('city', function ($cityQuery) use ($request) {
                        $cityQuery->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        if ($request->has('state_id') && $request->state_id) {
            $query->where('state_id', $request->state_id);
        }

        if ($request->has('city_id') && $request->city_id) {
            $query->where('city_id', $request->city_id);
        }

        if ($request->has('visit_status') && $request->visit_status !== 'all') {
            $query->whereHas('visits', function ($q) use ($request) {
                $q->where('status', $request->visit_status);
            });
        }

        $perPage = $request->get('per_page', 15);
        $stores = $query->orderBy('name')->paginate($perPage);

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

            if ($latestVisit) {
                $surveyAnswers = $latestVisit->questionAnswers;

                // Find breakage answer: question with is_count=true and text containing 'breakage'
                $breakageAnswer = $surveyAnswers->first(function ($a) {
                    return $a->question &&
                        $a->question->is_count &&
                        stripos($a->question->question_text, 'breakage') !== false;
                });

                $store->survey_stats = [
                    'total' => $surveyAnswers->count(),
                    'pending' => $surveyAnswers->where('admin_status', 'pending')->count(),
                    'approved' => $surveyAnswers->where('admin_status', 'approved')->count(),
                    'rejected' => $surveyAnswers->where('admin_status', 'rejected')->count(),
                    'needs_review' => $surveyAnswers->where('admin_status', 'needs_review')->count(),
                    'breakage_count' => $breakageAnswer?->count ?? null,  // null = no breakage question answered
                ];

                $stockTransactions = $latestVisit->stockTransactions;
                $store->stock_stats = [
                    'total' => $stockTransactions->count(),
                    'pending' => $stockTransactions->where('status', 'pending')->count(),
                    'approved' => $stockTransactions->where('status', 'approved')->count(),
                    'delivered' => $stockTransactions->where('status', 'delivered')->count(),
                    'returned' => $stockTransactions->where('status', 'returned')->count(),
                    'rejected' => $stockTransactions->where('status', 'rejected')->count(),
                ];

                $orders = $latestVisit->orders;
                $store->order_stats = [
                    'total' => $orders->count(),
                    'pending' => $orders->where('status', 'pending')->count(),
                    'confirmed' => $orders->where('status', 'confirmed')->count(),
                    'delivered' => $orders->where('status', 'delivered')->count(),
                    'total_amount' => $orders->sum('total_amount'),
                ];
            } else {
                $store->survey_stats = [
                    'total' => 0,
                    'pending' => 0,
                    'approved' => 0,
                    'rejected' => 0,
                    'needs_review' => 0,
                    'breakage_count' => null,
                ];
                $store->stock_stats = [
                    'total' => 0,
                    'pending' => 0,
                    'approved' => 0,
                    'delivered' => 0,
                    'returned' => 0,
                    'rejected' => 0,
                ];
                $store->order_stats = [
                    'total' => 0,
                    'pending' => 0,
                    'confirmed' => 0,
                    'delivered' => 0,
                    'total_amount' => 0,
                ];
            }

            return $store;
        });

        $stateIds = RoleAccessHelper::getAccessibleStateIds();
        $states = State::whereIn('id', $stateIds)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $statistics = [
            'total_stores' => $query->count(),
            'visited_today' => StoreVisit::whereDate('visit_date', today())->count(),
            'pending_surveys' => QuestionAnswer::where('admin_status', 'pending')->count(),
            'pending_stock' => StockTransaction::where('status', 'pending')->count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'total_orders_value' => Order::whereDate('created_at', today())->sum('total_amount'),
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

        // Get ALL visits for this store with complete history
        $visits = StoreVisit::where('store_id', $storeId)
            ->with([
                'employee.user',
                'employee.manager',
                'questionAnswers.question',
                'questionAnswers.reviewer',
                'stockTransactions.product',
                'stockTransactions.approvedBy',
                'orders.items.product',
                'orders.offer'
            ])
            ->orderBy('visit_date', 'desc')
            ->orderBy('check_in_time', 'desc')
            ->get();

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

    public function markStockDelivered(Request $request, $transactionId)
    {
        $request->validate([
            'admin_remark' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $transaction = StockTransaction::with('order.items')->findOrFail($transactionId);

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

            // Mark transaction as delivered
            $transaction->update([
                'status' => 'delivered',
                'admin_remark' => $request->admin_remark ?? $transaction->admin_remark,
            ]);

            // Update stock for this specific product
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

            $product = Product::findOrFail($transaction->product_id);
            $product->decrement('total_stock', $transaction->quantity);

            // If linked to an order, check if ALL items are delivered
            if ($transaction->order_id) {
                $order = $transaction->order;

                // Check if all stock transactions for this order are delivered
                $allDelivered = $order->stockTransactions()
                    ->where('status', '!=', 'delivered')
                    ->count() === 0;

                if ($allDelivered) {
                    // Update order status to delivered
                    $order->update([
                        'status' => 'delivered',
                        'delivered_at' => now(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Stock delivered successfully. Added {$transaction->quantity} units to store.",
                'data' => [
                    'new_current_stock' => $storeProduct->current_stock,
                    'new_pending_stock' => $storeProduct->pending_stock,
                    'order_delivered' => $transaction->order_id && $allDelivered,
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

    public function bulkApproveVisit(Request $request, $visitId)
    {
        $request->validate([
            'admin_remark' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $surveyCount = QuestionAnswer::where('visit_id', $visitId)
                ->where('admin_status', 'pending')
                ->update([
                    'admin_status' => 'approved',
                    'admin_remark' => $request->admin_remark,
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                ]);

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

    // NEW: Order Management Methods
    public function updateOrderStatus(Request $request, $orderId)
    {
        $request->validate([
            'status' => 'required|in:confirmed,delivered,cancelled',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $order = Order::with(['items.product', 'stockTransactions'])->findOrFail($orderId);

            $order->status = $request->status;

            if ($request->status === 'confirmed') {
                $order->confirmed_at = now();

                // Approve all linked stock transactions
                foreach ($order->stockTransactions as $stockTransaction) {
                    if ($stockTransaction->status === 'pending') {
                        $stockTransaction->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                    }
                }
                $this->regenerateInvoice($order);

            } elseif ($request->status === 'delivered') {
                $order->delivered_at = now();

                // Update stock for each item
                foreach ($order->items as $item) {
                    $storeProduct = \App\Models\StoreProduct::firstOrCreate(
                        [
                            'store_id' => $order->store_id,
                            'product_id' => $item->product_id,
                        ],
                        [
                            'current_stock' => 0,
                            'pending_stock' => 0,
                            'return_stock' => 0,
                        ]
                    );

                    // Move from pending to current
                    $storeProduct->decrement('pending_stock', $item->quantity);
                    $storeProduct->increment('current_stock', $item->quantity);

                    // Update product total stock
                    $product = Product::findOrFail($item->product_id);
                    $product->decrement('total_stock', $item->quantity);
                }

                // Mark all linked stock transactions as delivered
                foreach ($order->stockTransactions as $stockTransaction) {
                    $stockTransaction->update([
                        'status' => 'delivered',
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]);
                }
                $this->regenerateInvoice($order);

            } elseif ($request->status === 'cancelled') {
                // Reverse pending stock for cancelled orders
                foreach ($order->items as $item) {
                    $storeProduct = \App\Models\StoreProduct::where('store_id', $order->store_id)
                        ->where('product_id', $item->product_id)
                        ->first();

                    if ($storeProduct) {
                        $storeProduct->decrement('pending_stock', $item->quantity);
                    }
                }

                // Cancel all linked stock transactions
                foreach ($order->stockTransactions as $stockTransaction) {
                    $stockTransaction->update([
                        'status' => 'rejected',
                        'admin_remark' => 'Order cancelled',
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]);
                }
                $this->regenerateInvoice($order);
            }

            if ($request->notes) {
                $order->notes = $request->notes;
            }

            $order->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Order status updated to {$request->status}",
                'data' => $order->fresh(['items.product', 'stockTransactions'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order status update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status'
            ], 500);
        }
    }

    private function regenerateInvoice(Order $order)
    {
        // Delete old invoice if exists
        if ($order->invoice_pdf_path) {
            \Storage::disk('public')->delete($order->invoice_pdf_path);
        }

        // Load relationships needed for invoice
        $order->load(['items.product.pCategory', 'store.state', 'store.city', 'employee', 'visit', 'offer']);

        // Generate new PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.order-invoice', ['order' => $order]);

        $storeName = \Str::slug($order->store->name);
        $fileName = "invoice_{$order->order_number}.pdf";
        $folderPath = "invoices/{$storeName}";
        $filePath = "{$folderPath}/{$fileName}";

        \Storage::disk('public')->put($filePath, $pdf->output());

        // Update order with new path
        $order->update(['invoice_pdf_path' => $filePath]);

        return $filePath;
    }

    public function bulkDeliverOrder(Request $request, $orderId)
    {
        $request->validate([
            'admin_remark' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $order = Order::with(['items.product', 'stockTransactions'])->findOrFail($orderId);

            if ($order->status === 'delivered') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order already delivered'
                ], 400);
            }

            // Update order
            $order->update([
                'status' => 'delivered',
                'delivered_at' => now(),
                'notes' => $request->admin_remark ?? $order->notes,
            ]);

            // Update stock for each item
            foreach ($order->items as $item) {
                $storeProduct = \App\Models\StoreProduct::firstOrCreate(
                    [
                        'store_id' => $order->store_id,
                        'product_id' => $item->product_id,
                    ],
                    [
                        'current_stock' => 0,
                        'pending_stock' => 0,
                        'return_stock' => 0,
                    ]
                );

                $storeProduct->decrement('pending_stock', $item->quantity);
                $storeProduct->increment('current_stock', $item->quantity);

                $product = Product::findOrFail($item->product_id);
                $product->decrement('total_stock', $item->quantity);
            }

            // Mark all stock transactions as delivered
            foreach ($order->stockTransactions as $stockTransaction) {
                $stockTransaction->update([
                    'status' => 'delivered',
                    'admin_remark' => $request->admin_remark,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
            }
            $this->regenerateInvoice($order);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order marked as delivered successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk deliver order failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to deliver order'
            ], 500);
        }
    }

    public function getOrderDetails($orderId)
    {
        $order = Order::with([
            'items.product',
            'store',
            'employee',
            'visit',
            'offer'
        ])->findOrFail($orderId);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }
}
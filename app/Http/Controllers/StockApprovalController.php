<?php

namespace App\Http\Controllers;

use App\Models\StockTransaction;
use App\Models\StoreProduct;
use App\Models\Product;
use App\Models\Store;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class StockApprovalController extends Controller
{
    public function index(Request $request)
    {
        $query = StockTransaction::with([
            'employee.user',
            'store.state',
            'store.city',
            'store.area',
            'product',
            'visit',
            'approvedBy'
        ]);

        // Filter by status
        $status = $request->get('status', 'pending');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Filter by employee
        if ($request->has('employee_id') && $request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by store
        if ($request->has('store_id') && $request->store_id) {
            $query->where('store_id', $request->store_id);
        }

        // Filter by product
        if ($request->has('product_id') && $request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by date range
        if ($request->has('from_date') && $request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('employee', function ($empQuery) use ($request) {
                    $empQuery->where('name', 'like', '%' . $request->search . '%');
                })->orWhereHas('store', function ($storeQuery) use ($request) {
                    $storeQuery->where('name', 'like', '%' . $request->search . '%');
                })->orWhereHas('product', function ($productQuery) use ($request) {
                    $productQuery->where('name', 'like', '%' . $request->search . '%');
                });
            });
        }

        $perPage = $request->get('per_page', 15);
        $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Get filter data
        $employees = Employee::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $stores = Store::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $products = Product::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        // Get counts for status tabs
        $statusCounts = [
            'pending' => StockTransaction::where('status', 'pending')->count(),
            'approved' => StockTransaction::where('status', 'approved')->count(),
            'delivered' => StockTransaction::where('status', 'delivered')->count(),
            'returned' => StockTransaction::where('status', 'returned')->count(),
            'rejected' => StockTransaction::where('status', 'rejected')->count(),
        ];

        return Inertia::render('StockApproval/Index', [
            'records' => $transactions,
            'employees' => $employees,
            'stores' => $stores,
            'products' => $products,
            'statusCounts' => $statusCounts,
            'filters' => [
                'search' => $request->search,
                'status' => $status,
                'type' => $request->type,
                'employee_id' => $request->employee_id,
                'store_id' => $request->store_id,
                'product_id' => $request->product_id,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function show($id)
    {
        $transaction = StockTransaction::with([
            'employee.user',
            'store.state',
            'store.city',
            'store.area',
            'visit.questionAnswers.question',
            'approvedBy'
        ])->findOrFail($id);

        return Inertia::render('StockApproval/Details', [
            'transaction' => $transaction,
        ]);
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'admin_remark' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $transaction = StockTransaction::findOrFail($id);

            if ($transaction->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending transactions can be approved'
                ], 400);
            }

            // Update transaction status
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
            Log::error('Transaction approval failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve transaction'
            ], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'admin_remark' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $transaction = StockTransaction::findOrFail($id);

            if ($transaction->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending transactions can be rejected'
                ], 400);
            }

            // Update transaction status
            $transaction->update([
                'status' => 'rejected',
                'admin_remark' => $request->admin_remark,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Remove from pending/return stock
            $storeProduct = StoreProduct::where('store_id', $transaction->store_id)
                ->where('product_id', $transaction->product_id)
                ->first();

            if ($storeProduct) {
                if ($transaction->type === 'add') {
                    // Decrease pending stock
                    $storeProduct->decrement('pending_stock', $transaction->quantity);
                } else {
                    // Decrease return stock
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
            Log::error('Transaction rejection failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject transaction'
            ], 500);
        }
    }

    public function markDelivered(Request $request, $id)
    {
        $request->validate([
            'admin_remark' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $transaction = StockTransaction::findOrFail($id);

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

            // Update transaction status
            $transaction->update([
                'status' => 'delivered',
                'admin_remark' => $request->admin_remark ?? $transaction->admin_remark,
            ]);

            // Update stock: ADD to current_stock, REMOVE from pending_stock
            $storeProduct = StoreProduct::firstOrCreate(
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

            // Add to current stock
            $storeProduct->increment('current_stock', $transaction->quantity);

            // Remove from pending stock
            $storeProduct->decrement('pending_stock', $transaction->quantity);

            // Also update product total_stock
            $product = Product::findOrFail($transaction->product_id);
            $product->increment('total_stock', $transaction->quantity);

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

    public function markReturned(Request $request, $id)
    {
        $request->validate([
            'admin_remark' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $transaction = StockTransaction::findOrFail($id);

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

            // Get store product
            $storeProduct = StoreProduct::where('store_id', $transaction->store_id)
                ->where('product_id', $transaction->product_id)
                ->first();

            if (!$storeProduct) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store product not found'
                ], 404);
            }

            // Check if enough stock to return
            if ($storeProduct->current_stock < $transaction->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient stock. Current stock: {$storeProduct->current_stock}, Return qty: {$transaction->quantity}"
                ], 400);
            }

            // Update transaction status
            $transaction->update([
                'status' => 'returned',
                'admin_remark' => $request->admin_remark ?? $transaction->admin_remark,
            ]);

            // Update stock: SUBTRACT from current_stock, REMOVE from return_stock
            $storeProduct->decrement('current_stock', $transaction->quantity);
            $storeProduct->decrement('return_stock', $transaction->quantity);

            // Also update product total_stock
            $product = Product::findOrFail($transaction->product_id);
            $product->decrement('total_stock', $transaction->quantity);

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

    public function bulkApprove(Request $request)
    {
        $request->validate([
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:stock_transactions,id',
            'admin_remark' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $count = StockTransaction::whereIn('id', $request->transaction_ids)
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
                'message' => "{$count} transactions approved successfully"
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk approval failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve transactions'
            ], 500);
        }
    }

    public function statistics()
    {
        $stats = [
            'total_pending' => StockTransaction::where('status', 'pending')->count(),
            'total_approved_today' => StockTransaction::where('status', 'approved')
                ->whereDate('approved_at', today())
                ->count(),
            'total_delivered_today' => StockTransaction::where('status', 'delivered')
                ->whereDate('updated_at', today())
                ->count(),
            'total_returned_today' => StockTransaction::where('status', 'returned')
                ->whereDate('updated_at', today())
                ->count(),
            'pending_value' => StockTransaction::where('status', 'pending')
                ->join('products', 'stock_transactions.product_id', '=', 'products.id')
                ->selectRaw('SUM(stock_transactions.quantity * products.mrp) as total')
                ->value('total') ?? 0,
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
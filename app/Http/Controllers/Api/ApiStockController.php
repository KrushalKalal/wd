<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockTransaction;
use App\Models\StoreVisit;
use App\Models\StoreProduct;
use Illuminate\Http\Request;

class ApiStockController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'visit_id' => 'required|exists:store_visits,id',
            'store_id' => 'required|exists:stores,id',
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:add,return',
            'quantity' => 'required|integer|min:1',
            'remark' => 'nullable|string',
        ]);

        $employee = $request->user()->employee;

        // Verify visit belongs to employee
        $visit = StoreVisit::where('id', $request->visit_id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        try {
            $transaction = StockTransaction::create([
                'store_id' => $request->store_id,
                'product_id' => $request->product_id,
                'employee_id' => $employee->id,
                'visit_id' => $request->visit_id,
                'type' => $request->type,
                'quantity' => $request->quantity,
                'status' => 'pending',
                'remark' => $request->remark,
            ]);

            // Update pending/return stock
            $storeProduct = StoreProduct::firstOrCreate(
                [
                    'store_id' => $request->store_id,
                    'product_id' => $request->product_id,
                ],
                [
                    'current_stock' => 0,
                    'pending_stock' => 0,
                    'return_stock' => 0,
                ]
            );

            if ($request->type === 'add') {
                $storeProduct->increment('pending_stock', $request->quantity);
            } else {
                $storeProduct->increment('return_stock', $request->quantity);
            }

            return response()->json([
                'success' => true,
                'message' => 'Stock transaction submitted successfully',
                'data' => $transaction->fresh()->load(['product', 'store'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getMyTransactions(Request $request)
    {
        $employee = $request->user()->employee;

        $transactions = StockTransaction::where('employee_id', $employee->id)
            ->with(['product', 'store', 'visit'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }
}
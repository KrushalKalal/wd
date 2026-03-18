<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Offer;
use App\Models\StoreVisit;
use App\Models\StoreProduct;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class ApiStockController extends Controller
{
    /**
     * UNIFIED CREATE METHOD
     * Handles both:
     * - type: add → Creates Order + Stock Transactions (with invoice)
     * - type: return → Creates only Stock Transactions (no invoice)
     */
    public function create(Request $request)
    {
        $request->validate([
            'visit_id' => 'required|exists:store_visits,id',
            'store_id' => 'required|exists:stores,id',
            'type' => 'required|in:add,return',

            // For multi-product support
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',

            // Only for type: add
            'offer_id' => 'nullable|exists:offers,id',
            'promocode' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $employee = $request->user()->employee;

        // Verify visit belongs to employee
        $visit = StoreVisit::where('id', $request->visit_id)
            ->where('employee_id', $employee->id)
            ->where('store_id', $request->store_id)
            ->firstOrFail();

        try {
            DB::beginTransaction();

            if ($request->type === 'add') {
                // TYPE ADD = ORDER (with invoice, offers, etc.)
                $result = $this->handleAddType($request, $employee, $visit);
            } else {
                // TYPE RETURN = Simple stock return (no order, no invoice)
                $result = $this->handleReturnType($request, $employee, $visit);
            }

            DB::commit();

            return response()->json($result);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle TYPE: ADD
     * Creates Order + OrderItems + StockTransactions + Invoice
     */
    private function handleAddType($request, $employee, $visit)
    {
        // 1. Calculate order totals
        $calculation = $this->calculateOrderTotals(
            $request->items,
            $request->offer_id,
            $request->promocode,
            $request->store_id,
            $visit->store->state_id
        );

        if (!$calculation['success']) {
            throw new \Exception($calculation['message']);
        }

        // 2. Create Order
        $order = Order::create([
            'store_id' => $request->store_id,
            'employee_id' => $employee->id,
            'visit_id' => $request->visit_id,
            'subtotal' => $calculation['subtotal'],
            'offer_discount' => $calculation['offer_discount'],
            'promocode_discount' => $calculation['promocode_discount'],
            'taxable_amount' => $calculation['taxable_amount'],
            'cgst' => $calculation['cgst'],
            'sgst' => $calculation['sgst'],
            'igst' => $calculation['igst'],
            'total_amount' => $calculation['total_amount'],
            'offer_id' => $request->offer_id,
            'promocode' => $calculation['promocode'],
            'promocode_discount_percentage' => $calculation['promocode_percentage'],
            'status' => 'pending',
            'notes' => $request->notes,
        ]);

        // 3. Create Order Items + Stock Transactions (linked)
        foreach ($request->items as $item) {
            $product = Product::findOrFail($item['product_id']);

            $itemSubtotal = $product->mrp * $item['quantity'];
            $itemDiscount = ($calculation['offer_discount'] + $calculation['promocode_discount'])
                * ($itemSubtotal / $calculation['subtotal']);
            $itemTotal = $itemSubtotal - $itemDiscount;

            // Create Order Item
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $product->mrp,
                'subtotal' => $itemSubtotal,
                'discount' => $itemDiscount,
                'total' => $itemTotal,
            ]);

            // Create Stock Transaction (linked to order)
            StockTransaction::create([
                'store_id' => $request->store_id,
                'product_id' => $item['product_id'],
                'employee_id' => $employee->id,
                'visit_id' => $request->visit_id,
                'order_id' => $order->id, // LINKED
                'type' => 'add',
                'quantity' => $item['quantity'],
                'status' => 'pending',
                'remark' => 'Auto-generated from Order #' . $order->order_number,
            ]);

            // Update pending stock
            $storeProduct = StoreProduct::firstOrCreate(
                [
                    'store_id' => $request->store_id,
                    'product_id' => $item['product_id'],
                ],
                [
                    'current_stock' => 0,
                    'pending_stock' => 0,
                    'return_stock' => 0,
                ]
            );

            $storeProduct->increment('pending_stock', $item['quantity']);
        }

        // 4. Generate Invoice PDF
        $this->generateInvoice($order);

        return [
            'success' => true,
            'message' => 'Order created successfully',
            'data' => [
                'order' => $order->fresh()->load(['items.product', 'store', 'offer']),
                'invoice_url' => $order->invoice_pdf_path ? asset('storage/' . $order->invoice_pdf_path) : null,
            ]
        ];
    }

    /**
     * Handle TYPE: RETURN
     * Creates only StockTransactions (no order, no invoice)
     */
    private function handleReturnType($request, $employee, $visit)
    {
        $transactions = [];

        foreach ($request->items as $item) {
            // Create Stock Transaction (no order_id)
            $transaction = StockTransaction::create([
                'store_id' => $request->store_id,
                'product_id' => $item['product_id'],
                'employee_id' => $employee->id,
                'visit_id' => $request->visit_id,
                'order_id' => null, // NO ORDER
                'type' => 'return',
                'quantity' => $item['quantity'],
                'status' => 'pending',
                'remark' => $request->notes,
            ]);

            // Update return stock
            $storeProduct = StoreProduct::firstOrCreate(
                [
                    'store_id' => $request->store_id,
                    'product_id' => $item['product_id'],
                ],
                [
                    'current_stock' => 0,
                    'pending_stock' => 0,
                    'return_stock' => 0,
                ]
            );

            $storeProduct->increment('return_stock', $item['quantity']);

            $transactions[] = $transaction->fresh()->load(['product', 'store']);
        }

        return [
            'success' => true,
            'message' => 'Return transaction submitted successfully',
            'data' => $transactions
        ];
    }

    /**
     * Calculate order totals with offers, promocodes, taxes
     */
    private function calculateOrderTotals($items, $offerId, $promocode, $storeId, $stateId)
    {
        $subtotal = 0;

        // Calculate subtotal
        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            if (!$product) {
                return [
                    'success' => false,
                    'message' => 'Product not found: ' . $item['product_id']
                ];
            }
            $subtotal += $product->mrp * $item['quantity'];
        }

        $offerDiscount = 0;
        $promocodeDiscount = 0;
        $promocodePercentage = 0;
        $validPromocode = null;

        // Apply Offer Discount
        if ($offerId) {
            $offer = Offer::active()->find($offerId);
            if ($offer) {
                $offerDiscount = ($subtotal * $offer->offer_percentage) / 100;
            }
        }

        // Apply Promocode Discount
        if ($promocode) {
            $promoEmployee = Employee::where('promocode', strtoupper($promocode))
                ->where('promocode_active', true)
                ->first();

            if ($promoEmployee && $promoEmployee->promocode_discount_percentage > 0) {
                $amountAfterOffer = $subtotal - $offerDiscount;
                $promocodeDiscount = ($amountAfterOffer * $promoEmployee->promocode_discount_percentage) / 100;
                $promocodePercentage = $promoEmployee->promocode_discount_percentage;
                $validPromocode = $promoEmployee->promocode;
            }
        }

        $taxableAmount = $subtotal - $offerDiscount - $promocodeDiscount;

        // Get company state for tax calculation
        // TODO: Replace with your actual company state_id logic
        $companyStateId = 1; // Example: Gujarat

        $cgst = 0;
        $sgst = 0;
        $igst = 0;

        if ($stateId == $companyStateId) {
            // Same state: CGST + SGST
            $cgst = ($taxableAmount * 9) / 100;
            $sgst = ($taxableAmount * 9) / 100;
        } else {
            // Different state: IGST
            $igst = ($taxableAmount * 18) / 100;
        }

        $totalAmount = $taxableAmount + $cgst + $sgst + $igst;

        return [
            'success' => true,
            'subtotal' => round($subtotal, 2),
            'offer_discount' => round($offerDiscount, 2),
            'promocode_discount' => round($promocodeDiscount, 2),
            'promocode' => $validPromocode,
            'promocode_percentage' => $promocodePercentage,
            'taxable_amount' => round($taxableAmount, 2),
            'cgst' => round($cgst, 2),
            'sgst' => round($sgst, 2),
            'igst' => round($igst, 2),
            'total_amount' => round($totalAmount, 2),
        ];
    }

    /**
     * Generate Invoice PDF
     */
    private function generateInvoice(Order $order)
    {
        $order->load(['items.product.pCategory', 'store.state', 'store.city', 'employee', 'visit', 'offer']);

        $pdf = Pdf::loadView('invoices.order-invoice', ['order' => $order]);

        $storeName = Str::slug($order->store->name);
        $fileName = "invoice_{$order->order_number}.pdf";
        $folderPath = "invoices/{$storeName}";
        $filePath = "{$folderPath}/{$fileName}";

        \Storage::disk('public')->put($filePath, $pdf->output());

        $order->update(['invoice_pdf_path' => $filePath]);
    }

    /**
     * Get my transactions (both orders and returns)
     */
    public function getMyTransactions(Request $request)
    {
        $employee = $request->user()->employee;

        $query = StockTransaction::where('employee_id', $employee->id)
            ->with(['product', 'store', 'visit', 'order']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Filter by store
        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        // ✅ Group by store
        $grouped = $transactions->groupBy('store_id')->values()->map(function ($group) {
            $first = $group->first();

            return [
                'store_id' => $first->store->id,
                'store_name' => $first->store->name,

                // Optional: summary counts
                'summary' => [
                    'total_transactions' => $group->count(),
                    'approved' => $group->where('status', 'approved')->count(),
                    'pending' => $group->where('status', 'pending')->count(),
                    'rejected' => $group->where('status', 'rejected')->count(),
                ],

                'transactions' => $group->map(function ($txn) {
                    $data = [
                        'id' => $txn->id,
                        'product_name' => $txn->product->name,
                        'type' => $txn->type,
                        'quantity' => $txn->quantity,
                        'status' => $txn->status,
                        'remark' => $txn->remark,
                        'admin_remark' => $txn->admin_remark,
                        'created_at' => $txn->created_at->toDateTimeString(),
                    ];

                    if ($txn->order_id) {
                        $data['order'] = [
                            'id' => $txn->order->id,
                            'order_number' => $txn->order->order_number,
                            'total_amount' => $txn->order->total_amount,
                            'invoice_url' => $txn->order->invoice_pdf_path
                                ? asset('storage/' . $txn->order->invoice_pdf_path)
                                : null,
                        ];
                    }

                    return $data;
                })->values()
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $grouped,
        ]);
    }


    public function getStoreStockHistory(Request $request, $storeId)
    {
        $employee = $request->user()->employee;

        $transactions = StockTransaction::where('store_id', $storeId)
            ->where('employee_id', $employee->id)
            ->with(['product', 'visit', 'order'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }
}
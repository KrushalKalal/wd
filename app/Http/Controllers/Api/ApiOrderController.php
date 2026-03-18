<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Offer;
use App\Models\StoreVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class ApiOrderController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'visit_id' => 'required|exists:store_visits,id',
            'store_id' => 'required|exists:stores,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
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

            // Calculate order totals
            $subtotal = 0;
            $orderItems = [];

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $quantity = $item['quantity'];
                $unitPrice = $product->mrp;
                $itemSubtotal = $unitPrice * $quantity;

                $subtotal += $itemSubtotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $itemSubtotal,
                    'discount' => 0,
                    'total' => $itemSubtotal,
                ];
            }

            // Apply offer discount
            $offerDiscount = 0;
            $offerId = null;
            if ($request->offer_id) {
                $offer = Offer::active()->find($request->offer_id);
                if ($offer) {
                    $offerDiscount = ($subtotal * $offer->offer_percentage) / 100;
                    $offerId = $offer->id;
                }
            }

            // Apply promocode discount
            $promocodeDiscount = 0;
            $promocode = null;
            $promocodePercentage = 0;
            if ($request->promocode) {
                $promoEmployee = \App\Models\Employee::where('promocode', strtoupper($request->promocode))
                    ->where('promocode_active', true)
                    ->first();

                if ($promoEmployee) {
                    $promocode = $promoEmployee->promocode;
                    $promocodePercentage = $promoEmployee->promocode_discount_percentage;
                    $promocodeDiscount = (($subtotal - $offerDiscount) * $promocodePercentage) / 100;
                }
            }

            $taxableAmount = $subtotal - $offerDiscount - $promocodeDiscount;

            // Calculate GST (assuming 18% total - 9% CGST + 9% SGST or 18% IGST)
            $gstRate = 18;
            $store = $visit->store()->with('state')->first();

            // If same state, CGST + SGST, else IGST
            $cgst = 0;
            $sgst = 0;
            $igst = 0;

            // You can add logic to check if inter-state
            $isSameState = true; // Modify based on your business logic

            if ($isSameState) {
                $cgst = ($taxableAmount * ($gstRate / 2)) / 100;
                $sgst = ($taxableAmount * ($gstRate / 2)) / 100;
            } else {
                $igst = ($taxableAmount * $gstRate) / 100;
            }

            $totalAmount = $taxableAmount + $cgst + $sgst + $igst;

            // Create order
            $order = Order::create([
                'store_id' => $request->store_id,
                'employee_id' => $employee->id,
                'visit_id' => $request->visit_id,
                'subtotal' => $subtotal,
                'offer_discount' => $offerDiscount,
                'promocode_discount' => $promocodeDiscount,
                'taxable_amount' => $taxableAmount,
                'cgst' => $cgst,
                'sgst' => $sgst,
                'igst' => $igst,
                'total_amount' => $totalAmount,
                'offer_id' => $offerId,
                'promocode' => $promocode,
                'promocode_discount_percentage' => $promocodePercentage,
                'status' => 'pending',
                'notes' => $request->notes,
            ]);

            // Create order items
            foreach ($orderItems as $itemData) {
                OrderItem::create(array_merge(['order_id' => $order->id], $itemData));
            }

            // Generate invoice PDF
            $this->generateInvoice($order);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $order->fresh()->load(['items.product', 'store', 'offer'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generateInvoice(Order $order)
    {
        $order->load(['items.product', 'store', 'employee', 'offer']);

        $pdf = Pdf::loadView('invoices.order-invoice', ['order' => $order]);

        $storeName = Str::slug($order->store->name);
        $fileName = "invoice_{$order->order_number}.pdf";
        $folderPath = "invoices/{$storeName}";
        $filePath = "{$folderPath}/{$fileName}";

        \Storage::disk('public')->put($filePath, $pdf->output());

        $order->update(['invoice_pdf_path' => $filePath]);
    }

    public function getMyOrders(Request $request)
    {
        $employee = $request->user()->employee;

        $query = Order::where('employee_id', $employee->id)
            ->with(['items.product', 'store', 'visit', 'offer']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by store
        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'store_name' => $order->store->name,
                    'total_amount' => $order->total_amount,
                    'status' => $order->status,
                    'created_at' => $order->created_at->toDateTimeString(),
                    'invoice_url' => $order->invoice_pdf_path ? asset('storage/' . $order->invoice_pdf_path) : null,
                    'items_count' => $order->items->count(),
                ];
            }),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function getOrderDetails(Request $request, $id)
    {
        $employee = $request->user()->employee;

        $order = Order::where('employee_id', $employee->id)
            ->with(['items.product', 'store', 'visit', 'offer', 'employee'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    public function getStoreOrderHistory(Request $request, $storeId)
    {
        $employee = $request->user()->employee;

        $orders = Order::where('store_id', $storeId)
            ->where('employee_id', $employee->id)
            ->with(['items.product', 'visit'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }
}
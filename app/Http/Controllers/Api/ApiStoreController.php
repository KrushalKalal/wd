<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\Employee;
use Illuminate\Http\Request;

class ApiStoreController extends Controller
{
    public function getAssignedStores(Request $request)
    {
        $employee = $request->user()->employee;

        $stores = $employee->assignedStores()->with([
            'state',
            'city',
            'area',
            'categoryOne',
            'categoryTwo',
            'categoryThree',
        ])->get();

        return response()->json([
            'success' => true,
            'data' => $stores->map(function ($store) {
                return [
                    'id' => $store->id,
                    'name' => $store->name,
                    'address' => $store->address,
                    'state' => $store->state?->name,
                    'city' => $store->city?->name,
                    'area' => $store->area?->name,
                    'latitude' => $store->latitude,
                    'longitude' => $store->longitude,
                    'contact' => $store->contact_number_1,
                    'email' => $store->email,
                    'assigned_date' => $store->pivot->assigned_date,
                ];
            })
        ]);
    }

    public function getStoreDetails(Request $request, $id)
    {
        $employee = $request->user()->employee;

        // Check if store is assigned to employee
        $store = $employee->assignedStores()->with([
            'state',
            'city',
            'area',
            'categoryOne',
            'categoryTwo',
            'categoryThree',
            'visits' => function ($q) {
                $q->latest('visit_date');
            }
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
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
                'status' => $store->visits->first()?->status,
            ]
        ]);
    }


    public function getStoreProducts(Request $request, $id)
    {
        $employee = $request->user()->employee;

        // Check if store is assigned to employee
        $store = $employee->assignedStores()->findOrFail($id);

        $products = StoreProduct::where('store_id', $id)
            ->with(['product.categoryOne', 'product.categoryTwo', 'product.categoryThree'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products->map(function ($sp) {
                return [
                    'id' => $sp->id,
                    'product_id' => $sp->product_id,
                    'product_name' => $sp->product->name,
                    'mrp' => $sp->product->mrp,
                    'current_stock' => $sp->current_stock,
                    'pending_stock' => $sp->pending_stock,
                    'return_stock' => $sp->return_stock,
                    'available_stock' => $sp->available_stock,
                    'category_one' => $sp->product->categoryOne?->name,
                    'category_two' => $sp->product->categoryTwo?->name,
                    'category_three' => $sp->product->categoryThree?->name,
                ];
            })
        ]);
    }
}
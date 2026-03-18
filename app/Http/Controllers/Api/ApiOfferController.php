<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\Request;

class ApiOfferController extends Controller
{
    public function getActiveOffers(Request $request)
    {
        $employee = $request->user()->employee;

        $offers = Offer::active()
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->with(['productCategory', 'categoryOne', 'categoryTwo', 'categoryThree'])
            ->get()
            ->map(function ($offer) {
                return [
                    'id' => $offer->id,
                    'title' => $offer->offer_title,
                    'description' => $offer->description,
                    'offer_percentage' => $offer->offer_percentage,
                    'offer_type' => $offer->offer_type,
                    'start_date' => $offer->start_date->toDateString(),
                    'end_date' => $offer->end_date->toDateString(),
                    'product_category' => $offer->productCategory?->name,
                    'store_categories' => [
                        'category_one' => $offer->categoryOne?->name,
                        'category_two' => $offer->categoryTwo?->name,
                        'category_three' => $offer->categoryThree?->name,
                    ],
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $offers
        ]);
    }

    public function validatePromocode(Request $request)
    {
        $request->validate([
            'promocode' => 'required|string',
        ]);

        $promocode = strtoupper($request->promocode);

        $employee = \App\Models\Employee::where('promocode', $promocode)
            ->where('promocode_active', true)
            ->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive promocode'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'promocode' => $employee->promocode,
                'discount_percentage' => $employee->promocode_discount_percentage,
                'employee_name' => $employee->name,
            ]
        ]);
    }
}
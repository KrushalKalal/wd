<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\LocationResolverHelper;
use App\Models\EmployeeStoreAssignment;
use App\Models\Store;
use App\Models\CategoryOne;
use App\Models\CategoryTwo;
use App\Models\CategoryThree;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApiStoreMasterController extends Controller
{
    /**
     * Employee creates a new store from app
     * Status = approved by default
     * Auto-assigns to the employee who created it
     */
    public function createStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'store_legal_name' => 'nullable|string|max:255',
            'store_incharge' => 'nullable|string|max:255',
            'address' => 'required|string',
            'state_name' => 'required|string',
            'city_name' => 'required|string',
            'area_name' => 'nullable|string',
            'pin_code' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'contact_number_1' => 'nullable|string|max:20',
            'contact_number_2' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'category_one_id' => 'nullable|exists:category_one,id',
            'category_two_id' => 'nullable|exists:category_two,id',
            'category_three_id' => 'nullable|exists:category_three,id',
        ]);

        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee profile not found',
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Resolve state, city, area from names
            // Employee's zone_id used for auto-creating state if not found
            $resolved = LocationResolverHelper::resolveLocation(
                $request->state_name,
                $request->city_name,
                $request->area_name ?? '',
                $employee->zone_id
            );

            if (!$resolved['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $resolved['error'],
                ], 422);
            }

            // Create the store
            $store = Store::create([
                'name' => $request->name,
                'store_legal_name' => $request->store_legal_name,
                'store_incharge' => $request->store_incharge,
                'address' => $request->address,
                'state_id' => $resolved['state_id'],
                'city_id' => $resolved['city_id'],
                'area_id' => $resolved['area_id'],
                'pin_code' => $request->pin_code,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'contact_number_1' => $request->contact_number_1,
                'contact_number_2' => $request->contact_number_2,
                'email' => $request->email,
                'category_one_id' => $request->category_one_id,
                'category_two_id' => $request->category_two_id,
                'category_three_id' => $request->category_three_id,
                'country' => 'India',
                'is_active' => false,
                'manual_stock_entry' => true,
                'created_by_employee_id' => $employee->id,
            ]);

            // Auto-assign store to the employee who created it
            // First deactivate if somehow already assigned (edge case)
            EmployeeStoreAssignment::where('employee_id', $employee->id)
                ->where('store_id', $store->id)
                ->where('is_active', true)
                ->update(['is_active' => false, 'removed_date' => now()]);

            // Create fresh assignment
            EmployeeStoreAssignment::create([
                'employee_id' => $employee->id,
                'store_id' => $store->id,
                'assigned_date' => now(),
                'is_active' => true,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Store created and assigned to you successfully',
                'data' => [
                    'store_id' => $store->id,
                    'store_name' => $store->name,
                    'state' => $resolved['state_name'],
                    'city' => $resolved['city_name'],
                    'area' => $resolved['area_name'],
                    'status' => $store->status,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('App store creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create store: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get category options for store creation form in app
     */
    public function getCategories()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'category_ones' => CategoryOne::where('is_active', true)
                    ->select('id', 'name')
                    ->orderBy('name')
                    ->get(),
                'category_twos' => CategoryTwo::where('is_active', true)
                    ->select('id', 'name')
                    ->orderBy('name')
                    ->get(),
                'category_threes' => CategoryThree::where('is_active', true)
                    ->select('id', 'name')
                    ->orderBy('name')
                    ->get(),
            ],
        ]);
    }
}
<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use App\Models\Zone;
use App\Models\State;
use App\Models\City;
use App\Models\Area;

class RoleAccessHelper
{
    /**
     * Apply role-based filtering to any query
     */
    public static function applyRoleFilter($query)
    {
        $user = Auth::user();

        if (!$user) {
            return $query;
        }

        // Master Admin and Country Head see everything
        if ($user->hasRole(['Master Admin', 'Country Head'])) {
            return $query;
        }

        $employee = $user->employee;
        if (!$employee) {
            return $query->whereRaw('1 = 0'); // Return empty if no employee record
        }

        $tableName = $query->getModel()->getTable();

        // Zonal Head - sees only their zone's data
        if ($user->hasRole('Zonal Head') && $employee->zone_id) {
            return self::filterByZone($query, $employee->zone_id, $tableName);
        }

        // State Head - sees only their state's data
        if ($user->hasRole('State Head') && $employee->state_id) {
            return self::filterByState($query, $employee->state_id, $tableName);
        }

        // City Head - sees only their city's data
        if ($user->hasRole('City Head') && $employee->city_id) {
            return self::filterByCity($query, $employee->city_id, $tableName);
        }

        // Sales Employee - sees only their assigned area's data
        if ($user->hasRole('Sales Employee')) {
            return self::filterBySalesEmployee($query, $employee, $tableName);
        }

        return $query;
    }

    /**
     * Filter by zone
     */
    private static function filterByZone($query, $zoneId, $tableName)
    {
        // Direct zone_id column (states, employees)
        if (\Schema::hasColumn($tableName, 'zone_id')) {
            return $query->where('zone_id', $zoneId);
        }

        // Through state (cities, areas, branches, etc.)
        if (\Schema::hasColumn($tableName, 'state_id')) {
            return $query->whereHas('state', function ($q) use ($zoneId) {
                $q->where('zone_id', $zoneId);
            });
        }

        // Through city->state (areas, branches, etc.)
        if (\Schema::hasColumn($tableName, 'city_id')) {
            return $query->whereHas('city.state', function ($q) use ($zoneId) {
                $q->where('zone_id', $zoneId);
            });
        }

        // Through area->city->state (branches, stores, etc.)
        if (\Schema::hasColumn($tableName, 'area_id')) {
            return $query->whereHas('area.city.state', function ($q) use ($zoneId) {
                $q->where('zone_id', $zoneId);
            });
        }

        // Through store->state (store_products)
        if (\Schema::hasColumn($tableName, 'store_id')) {
            return $query->whereHas('store.state', function ($q) use ($zoneId) {
                $q->where('zone_id', $zoneId);
            });
        }

        // **NEW: Through employee->zone (employee_targets, store_visits, etc.)**
        if (\Schema::hasColumn($tableName, 'employee_id')) {
            return $query->whereHas('employee', function ($q) use ($zoneId) {
                $q->where('zone_id', $zoneId);
            });
        }

        return $query;
    }

    /**
     * Filter by state
     */
    private static function filterByState($query, $stateId, $tableName)
    {
        // Direct state_id column
        if (\Schema::hasColumn($tableName, 'state_id')) {
            return $query->where('state_id', $stateId);
        }

        // Through city (areas, branches, etc.)
        if (\Schema::hasColumn($tableName, 'city_id')) {
            return $query->whereHas('city', function ($q) use ($stateId) {
                $q->where('state_id', $stateId);
            });
        }

        // Through area->city (branches, stores, etc.)
        if (\Schema::hasColumn($tableName, 'area_id')) {
            return $query->whereHas('area.city', function ($q) use ($stateId) {
                $q->where('state_id', $stateId);
            });
        }

        // Through store->state (store_products)
        if (\Schema::hasColumn($tableName, 'store_id')) {
            return $query->whereHas('store', function ($q) use ($stateId) {
                $q->where('state_id', $stateId);
            });
        }

        // **NEW: Through employee->state (employee_targets, store_visits, etc.)**
        if (\Schema::hasColumn($tableName, 'employee_id')) {
            return $query->whereHas('employee', function ($q) use ($stateId) {
                $q->where('state_id', $stateId);
            });
        }

        return $query;
    }

    /**
     * Filter by city
     */
    private static function filterByCity($query, $cityId, $tableName)
    {
        // Direct city_id column
        if (\Schema::hasColumn($tableName, 'city_id')) {
            return $query->where('city_id', $cityId);
        }

        // Through area (branches, stores, etc.)
        if (\Schema::hasColumn($tableName, 'area_id')) {
            return $query->whereHas('area', function ($q) use ($cityId) {
                $q->where('city_id', $cityId);
            });
        }

        // Through store->city (store_products)
        if (\Schema::hasColumn($tableName, 'store_id')) {
            return $query->whereHas('store', function ($q) use ($cityId) {
                $q->where('city_id', $cityId);
            });
        }

        // **NEW: Through employee->city (employee_targets, store_visits, etc.)**
        if (\Schema::hasColumn($tableName, 'employee_id')) {
            return $query->whereHas('employee', function ($q) use ($cityId) {
                $q->where('city_id', $cityId);
            });
        }

        return $query;
    }

    /**
     * Filter for sales employee
     */
    private static function filterBySalesEmployee($query, $employee, $tableName)
    {
        if ($employee->area_id && \Schema::hasColumn($tableName, 'area_id')) {
            return $query->where('area_id', $employee->area_id);
        }

        if ($employee->city_id && \Schema::hasColumn($tableName, 'city_id')) {
            return $query->where('city_id', $employee->city_id);
        }

        if ($employee->state_id && \Schema::hasColumn($tableName, 'state_id')) {
            return $query->where('state_id', $employee->state_id);
        }

        // Through store (store_products)
        if (\Schema::hasColumn($tableName, 'store_id')) {
            if ($employee->area_id) {
                return $query->whereHas('store', function ($q) use ($employee) {
                    $q->where('area_id', $employee->area_id);
                });
            }
            if ($employee->city_id) {
                return $query->whereHas('store', function ($q) use ($employee) {
                    $q->where('city_id', $employee->city_id);
                });
            }
            if ($employee->state_id) {
                return $query->whereHas('store', function ($q) use ($employee) {
                    $q->where('state_id', $employee->state_id);
                });
            }
        }

        // **NEW: Through employee_id - only show own records (employee_targets, store_visits)**
        if (\Schema::hasColumn($tableName, 'employee_id')) {
            return $query->where('employee_id', $employee->id);
        }

        return $query;
    }

    /**
     * Get accessible zone IDs for current user
     */
    public static function getAccessibleZoneIds()
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        if ($user->hasRole(['Master Admin', 'Country Head'])) {
            return Zone::pluck('id')->toArray();
        }

        $employee = $user->employee;

        if ($user->hasRole('Zonal Head') && $employee && $employee->zone_id) {
            return [$employee->zone_id];
        }

        return [];
    }

    /**
     * Get accessible state IDs for current user
     */
    public static function getAccessibleStateIds()
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        if ($user->hasRole(['Master Admin', 'Country Head'])) {
            return State::pluck('id')->toArray();
        }

        $employee = $user->employee;

        if ($user->hasRole('Zonal Head') && $employee && $employee->zone_id) {
            return State::where('zone_id', $employee->zone_id)->pluck('id')->toArray();
        }

        if ($user->hasRole('State Head') && $employee && $employee->state_id) {
            return [$employee->state_id];
        }

        return [];
    }

    /**
     * Get accessible city IDs for current user
     */
    public static function getAccessibleCityIds()
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        if ($user->hasRole(['Master Admin', 'Country Head'])) {
            return City::pluck('id')->toArray();
        }

        $employee = $user->employee;

        if ($user->hasRole('Zonal Head') && $employee && $employee->zone_id) {
            $stateIds = State::where('zone_id', $employee->zone_id)->pluck('id');
            return City::whereIn('state_id', $stateIds)->pluck('id')->toArray();
        }

        if ($user->hasRole('State Head') && $employee && $employee->state_id) {
            return City::where('state_id', $employee->state_id)->pluck('id')->toArray();
        }

        if ($user->hasRole('City Head') && $employee && $employee->city_id) {
            return [$employee->city_id];
        }

        return [];
    }

    /**
     * Get accessible area IDs for current user
     */
    public static function getAccessibleAreaIds()
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        if ($user->hasRole(['Master Admin', 'Country Head'])) {
            return Area::pluck('id')->toArray();
        }

        $employee = $user->employee;

        if ($user->hasRole('Zonal Head') && $employee && $employee->zone_id) {
            $stateIds = State::where('zone_id', $employee->zone_id)->pluck('id');
            $cityIds = City::whereIn('state_id', $stateIds)->pluck('id');
            return Area::whereIn('city_id', $cityIds)->pluck('id')->toArray();
        }

        if ($user->hasRole('State Head') && $employee && $employee->state_id) {
            $cityIds = City::where('state_id', $employee->state_id)->pluck('id');
            return Area::whereIn('city_id', $cityIds)->pluck('id')->toArray();
        }

        if ($user->hasRole('City Head') && $employee && $employee->city_id) {
            return Area::where('city_id', $employee->city_id)->pluck('id')->toArray();
        }

        return [];
    }

    /**
     * Get accessible employee IDs for current user (for dropdowns and filters)
     */
    public static function getAccessibleEmployeeIds()
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        // Master Admin and Country Head see all employees
        if ($user->hasRole(['Master Admin', 'Country Head'])) {
            return \App\Models\Employee::pluck('id')->toArray();
        }

        $employee = $user->employee;
        if (!$employee) {
            return [];
        }

        // Zonal Head - sees employees in their zone
        if ($user->hasRole('Zonal Head') && $employee->zone_id) {
            return \App\Models\Employee::where('zone_id', $employee->zone_id)
                ->pluck('id')
                ->toArray();
        }

        // State Head - sees employees in their state
        if ($user->hasRole('State Head') && $employee->state_id) {
            return \App\Models\Employee::where('state_id', $employee->state_id)
                ->pluck('id')
                ->toArray();
        }

        // City Head - sees employees in their city
        if ($user->hasRole('City Head') && $employee->city_id) {
            return \App\Models\Employee::where('city_id', $employee->city_id)
                ->pluck('id')
                ->toArray();
        }

        // Sales Employee - only sees themselves
        if ($user->hasRole('Sales Employee')) {
            return [$employee->id];
        }

        return [];
    }
}
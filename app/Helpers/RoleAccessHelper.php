<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use App\Models\Zone;
use App\Models\State;
use App\Models\City;
use App\Models\Area;
use App\Models\Employee;

class RoleAccessHelper
{
    // Role hierarchy — lower number = higher authority
    const ROLE_HIERARCHY = [
        'Master Admin' => 1,
        'Country Head' => 2,
        'Zonal Head' => 3,
        'State Head' => 4,
        'City Head' => 5,
        'On/Off Trade Head' => 6,
        'Sales Employee' => 7,
    ];

    // Who can create whom
    const CREATABLE_ROLES = [
        'Master Admin' => [
            'Country Head',
            'Zonal Head',
            'State Head',
            'City Head',
            'On/Off Trade Head',
            'Sales Employee'
        ],
        'Country Head' => [
            'Zonal Head',
            'State Head',
            'City Head',
            'On/Off Trade Head',
            'Sales Employee'
        ],
        'Zonal Head' => [
            'State Head',
            'City Head',
            'On/Off Trade Head',
            'Sales Employee'
        ],
        'State Head' => [
            'City Head',
            'On/Off Trade Head',
            'Sales Employee'
        ],
        'City Head' => [
            'On/Off Trade Head',
            'Sales Employee'
        ],
        'On/Off Trade Head' => ['Sales Employee'],
        'Sales Employee' => [],
    ];

    // Who reports to whom
    const MANAGER_ROLES = [
        'Country Head' => ['Master Admin'],
        'Zonal Head' => ['Country Head'],
        'State Head' => ['Zonal Head'],
        'City Head' => ['State Head'],
        'On/Off Trade Head' => ['City Head'],
        'Sales Employee' => ['On/Off Trade Head'],
    ];

    /**
     * Apply role-based filtering to any query
     */
    public static function applyRoleFilter($query)
    {
        $user = Auth::user();

        if (!$user) {
            return $query;
        }

        if ($user->hasRole(['Master Admin', 'Country Head'])) {
            return $query;
        }

        $employee = $user->employee;
        if (!$employee) {
            return $query->whereRaw('1 = 0');
        }

        $tableName = $query->getModel()->getTable();

        if ($user->hasRole('Zonal Head') && $employee->zone_id) {
            return self::filterByZone($query, $employee->zone_id, $tableName);
        }

        if ($user->hasRole('State Head') && $employee->state_id) {
            return self::filterByState($query, $employee->state_id, $tableName);
        }

        // City Head and On/Off Trade Head — same city level filter
        if ($user->hasRole(['City Head', 'On/Off Trade Head']) && $employee->city_id) {
            return self::filterByCity($query, $employee->city_id, $tableName);
        }

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
        if (\Schema::hasColumn($tableName, 'zone_id')) {
            return $query->where('zone_id', $zoneId);
        }

        if (\Schema::hasColumn($tableName, 'state_id')) {
            return $query->whereHas('state', function ($q) use ($zoneId) {
                $q->where('zone_id', $zoneId);
            });
        }

        if (\Schema::hasColumn($tableName, 'city_id')) {
            return $query->whereHas('city.state', function ($q) use ($zoneId) {
                $q->where('zone_id', $zoneId);
            });
        }

        if (\Schema::hasColumn($tableName, 'area_id')) {
            return $query->whereHas('area.city.state', function ($q) use ($zoneId) {
                $q->where('zone_id', $zoneId);
            });
        }

        if (\Schema::hasColumn($tableName, 'store_id')) {
            return $query->whereHas('store.state', function ($q) use ($zoneId) {
                $q->where('zone_id', $zoneId);
            });
        }

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
        if (\Schema::hasColumn($tableName, 'state_id')) {
            return $query->where('state_id', $stateId);
        }

        if (\Schema::hasColumn($tableName, 'city_id')) {
            return $query->whereHas('city', function ($q) use ($stateId) {
                $q->where('state_id', $stateId);
            });
        }

        if (\Schema::hasColumn($tableName, 'area_id')) {
            return $query->whereHas('area.city', function ($q) use ($stateId) {
                $q->where('state_id', $stateId);
            });
        }

        if (\Schema::hasColumn($tableName, 'store_id')) {
            return $query->whereHas('store', function ($q) use ($stateId) {
                $q->where('state_id', $stateId);
            });
        }

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
        if (\Schema::hasColumn($tableName, 'city_id')) {
            return $query->where('city_id', $cityId);
        }

        if (\Schema::hasColumn($tableName, 'area_id')) {
            return $query->whereHas('area', function ($q) use ($cityId) {
                $q->where('city_id', $cityId);
            });
        }

        if (\Schema::hasColumn($tableName, 'store_id')) {
            return $query->whereHas('store', function ($q) use ($cityId) {
                $q->where('city_id', $cityId);
            });
        }

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
        }

        if (\Schema::hasColumn($tableName, 'employee_id')) {
            return $query->where('employee_id', $employee->id);
        }

        return $query;
    }

    /**
     * Get location locks based on logged in user role
     */
    public static function getLocationLocks(): array
    {
        $user = Auth::user();
        $employee = $user->employee;

        if ($user->hasRole(['Master Admin', 'Country Head'])) {
            return [
                'zone_id' => false,
                'state_id' => false,
                'city_id' => false,
                'area_id' => false,
                'zone_name' => null,
                'state_name' => null,
                'city_name' => null,
            ];
        }

        if ($user->hasRole('Zonal Head')) {
            return [
                'zone_id' => true,
                'state_id' => false,
                'city_id' => false,
                'area_id' => false,
                'zone_name' => $employee?->zone?->name,
                'state_name' => null,
                'city_name' => null,
            ];
        }

        if ($user->hasRole('State Head')) {
            return [
                'zone_id' => true,
                'state_id' => true,
                'city_id' => false,
                'area_id' => false,
                'zone_name' => $employee?->zone?->name,
                'state_name' => $employee?->state?->name,
                'city_name' => null,
            ];
        }

        if ($user->hasRole(['City Head', 'On/Off Trade Head'])) {
            return [
                'zone_id' => true,
                'state_id' => true,
                'city_id' => true,
                'area_id' => false,
                'zone_name' => $employee?->zone?->name,
                'state_name' => $employee?->state?->name,
                'city_name' => $employee?->city?->name,
            ];
        }

        // Sales Employee — full lock
        return [
            'zone_id' => true,
            'state_id' => true,
            'city_id' => true,
            'area_id' => true,
            'zone_name' => $employee?->zone?->name,
            'state_name' => $employee?->state?->name,
            'city_name' => $employee?->city?->name,
        ];
    }

    /**
     * Get logged in user location for pre-filling forms
     */
    public static function getUserLocation(): array
    {
        $user = Auth::user();
        $employee = $user->employee;

        return [
            'zone_id' => $employee?->zone_id,
            'zone_name' => $employee?->zone?->name,
            'state_id' => $employee?->state_id,
            'state_name' => $employee?->state?->name,
            'city_id' => $employee?->city_id,
            'city_name' => $employee?->city?->name,
            'area_id' => $employee?->area_id,
            'area_name' => $employee?->area?->name,
        ];
    }

    /**
     * Get roles that logged in user can create
     */
    public static function getCreatableRoles(): array
    {
        $user = Auth::user();
        $userRole = $user->roles->first()?->name;

        return self::CREATABLE_ROLES[$userRole] ?? [];
    }

    /**
     * Get manager roles for a given role being created
     */
    public static function getManagerRoles(string $roleName): array
    {
        return self::MANAGER_ROLES[$roleName] ?? [];
    }

    /**
     * Validate location access for logged in user
     */
    public static function validateLocationAccess(
        ?int $stateId,
        ?int $cityId
    ): array {
        $user = Auth::user();
        $employee = $user->employee;

        if ($user->hasRole(['Master Admin', 'Country Head'])) {
            return ['valid' => true];
        }

        if ($user->hasRole('Zonal Head') && $employee?->zone_id) {
            $state = State::find($stateId);
            if ($state && $state->zone_id !== $employee->zone_id) {
                return [
                    'valid' => false,
                    'message' => "This address is outside your zone ({$employee->zone->name})."
                ];
            }
        }

        if ($user->hasRole('State Head') && $employee?->state_id) {
            if ($stateId && $stateId !== $employee->state_id) {
                return [
                    'valid' => false,
                    'message' => "This address is outside your state ({$employee->state->name})."
                ];
            }
        }

        if ($user->hasRole(['City Head', 'On/Off Trade Head']) && $employee?->city_id) {
            if ($cityId && $cityId !== $employee->city_id) {
                return [
                    'valid' => false,
                    'message' => "This address is outside your city ({$employee->city->name})."
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Get accessible zone IDs
     */
    public static function getAccessibleZoneIds(): array
    {
        $user = Auth::user();

        if (!$user)
            return [];

        if ($user->hasRole(['Master Admin', 'Country Head'])) {
            return Zone::pluck('id')->toArray();
        }

        $employee = $user->employee;

        if ($user->hasRole('Zonal Head') && $employee?->zone_id) {
            return [$employee->zone_id];
        }

        return [];
    }

    /**
     * Get accessible state IDs
     */
    public static function getAccessibleStateIds(): array
    {
        $user = Auth::user();

        if (!$user)
            return [];

        if ($user->hasRole(['Master Admin', 'Country Head'])) {
            return State::pluck('id')->toArray();
        }

        $employee = $user->employee;

        if ($user->hasRole('Zonal Head') && $employee?->zone_id) {
            return State::where('zone_id', $employee->zone_id)->pluck('id')->toArray();
        }

        if ($user->hasRole('State Head') && $employee?->state_id) {
            return [$employee->state_id];
        }

        return [];
    }

    /**
     * Get accessible city IDs
     */
    public static function getAccessibleCityIds(): array
    {
        $user = Auth::user();

        if (!$user)
            return [];

        if ($user->hasRole(['Master Admin', 'Country Head'])) {
            return City::pluck('id')->toArray();
        }

        $employee = $user->employee;

        if ($user->hasRole('Zonal Head') && $employee?->zone_id) {
            $stateIds = State::where('zone_id', $employee->zone_id)->pluck('id');
            return City::whereIn('state_id', $stateIds)->pluck('id')->toArray();
        }

        if ($user->hasRole('State Head') && $employee?->state_id) {
            return City::where('state_id', $employee->state_id)->pluck('id')->toArray();
        }

        if ($user->hasRole(['City Head', 'On/Off Trade Head']) && $employee?->city_id) {
            return [$employee->city_id];
        }

        return [];
    }

    /**
     * Get accessible area IDs
     */
    public static function getAccessibleAreaIds(): array
    {
        $user = Auth::user();

        if (!$user)
            return [];

        if ($user->hasRole(['Master Admin', 'Country Head'])) {
            return Area::pluck('id')->toArray();
        }

        $employee = $user->employee;

        if ($user->hasRole('Zonal Head') && $employee?->zone_id) {
            $stateIds = State::where('zone_id', $employee->zone_id)->pluck('id');
            $cityIds = City::whereIn('state_id', $stateIds)->pluck('id');
            return Area::whereIn('city_id', $cityIds)->pluck('id')->toArray();
        }

        if ($user->hasRole('State Head') && $employee?->state_id) {
            $cityIds = City::where('state_id', $employee->state_id)->pluck('id');
            return Area::whereIn('city_id', $cityIds)->pluck('id')->toArray();
        }

        if ($user->hasRole(['City Head', 'On/Off Trade Head']) && $employee?->city_id) {
            return Area::where('city_id', $employee->city_id)->pluck('id')->toArray();
        }

        return [];
    }

    /**
     * Get accessible employee IDs
     */
    public static function getAccessibleEmployeeIds(): array
    {
        $user = Auth::user();

        if (!$user)
            return [];

        if ($user->hasRole(['Master Admin', 'Country Head'])) {
            return Employee::pluck('id')->toArray();
        }

        $employee = $user->employee;
        if (!$employee)
            return [];

        if ($user->hasRole('Zonal Head') && $employee->zone_id) {
            return Employee::where('zone_id', $employee->zone_id)->pluck('id')->toArray();
        }

        if ($user->hasRole('State Head') && $employee->state_id) {
            return Employee::where('state_id', $employee->state_id)->pluck('id')->toArray();
        }

        if ($user->hasRole(['City Head', 'On/Off Trade Head']) && $employee->city_id) {
            return Employee::where('city_id', $employee->city_id)->pluck('id')->toArray();
        }

        if ($user->hasRole('Sales Employee')) {
            return [$employee->id];
        }

        return [];
    }
}
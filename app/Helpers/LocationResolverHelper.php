<?php

namespace App\Helpers;

use App\Models\Area;
use App\Models\City;
use App\Models\State;
use App\Models\Zone;
use Illuminate\Support\Facades\Auth;

class LocationResolverHelper
{
    /**
     * Resolve state and city from Google Maps returned names
     * zone_id is required for auto-creating state if not found
     */
    public static function resolveLocation(
        string $stateName,
        string $cityName,
        string $areaName = '',
        ?int $zoneId = null
    ): array {
        // Match state — case insensitive exact match
        $state = State::whereRaw(
            'LOWER(name) = ?',
            [strtolower(trim($stateName))]
        )->first();

        if (!$state) {
            // Cannot auto-create without zone_id
            if (!$zoneId) {
                return [
                    'success' => false,
                    'error' => "State '{$stateName}' is not in our system. Please select a zone first so we can add it automatically.",
                ];
            }

            // Auto-create state with provided zone_id
            $state = State::create([
                'name' => $stateName,
                'zone_id' => $zoneId,
                'is_active' => true,
            ]);
        }

        // Match city — case insensitive exact match under resolved state
        $city = City::whereRaw(
            'LOWER(name) = ?',
            [strtolower(trim($cityName))]
        )
            ->where('state_id', $state->id)
            ->first();

        if (!$city) {
            // Auto-create city under resolved state
            $city = City::create([
                'name' => $cityName,
                'state_id' => $state->id,
                'is_active' => true,
            ]);
        }

        // Match area
        $area = null;
        if ($areaName) {
            $area = Area::whereRaw(
                'LOWER(name) = ?',
                [strtolower(trim($areaName))]
            )
                ->where('city_id', $city->id)
                ->first();

            if (!$area) {
                $area = Area::create([
                    'name' => $areaName,
                    'city_id' => $city->id,
                    'state_id' => $state->id,
                    'is_active' => true,
                ]);
            }
        }

        return [
            'success' => true,
            'state_id' => $state->id,
            'state_name' => $state->name,
            'city_id' => $city->id,
            'city_name' => $city->name,
            'area_id' => $area?->id,
            'area_name' => $area?->name,
            'zone_id' => $state->zone_id,
            'zone_name' => $state->zone?->name,
        ];
    }

    /**
     * Get areas by city for dropdown
     */
    // public static function getAreasByCity(int $cityId): array
    // {
    //     return Area::where('city_id', $cityId)
    //         ->where('is_active', true)
    //         ->select('id', 'name')
    //         ->orderBy('name')
    //         ->get()
    //         ->toArray();
    // }
}
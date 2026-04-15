<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Zone;
use App\Models\State;
use App\Models\City;
use App\Models\Area;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Store;
use App\Models\Employee;
use App\Models\StoreVisit;
use App\Models\StockTransaction;
use App\Models\QuestionAnswer;
use App\Models\StoreFlag;
use App\Helpers\RoleAccessHelper;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $role = $user->getRoleNames()->first();
        $employee = $user->employee;

        $statistics = $this->getRoleBasedStatistics($user, $employee);
        $recentActivities = $this->getRecentActivities($user, $employee);
        $hierarchyBreakdown = $this->getHierarchyBreakdown($user, $employee);

        return Inertia::render('Dashboard', [
            'role' => $role,
            'statistics' => $statistics,
            'recentActivities' => $recentActivities,
            'hierarchyBreakdown' => $hierarchyBreakdown,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    private function getRoleBasedStatistics($user, $employee)
    {
        $stats = [];

        if ($user->hasRole(['Master Admin', 'Country Head'])) {

            $stats = [
                'zones' => Zone::where('is_active', true)->count(),
                'states' => State::where('is_active', true)->count(),
                'cities' => City::where('is_active', true)->count(),
                'areas' => Area::where('is_active', true)->count(),
                'branches' => Branch::where('is_active', true)->count(),
                'products' => Product::where('is_active', true)->count(),
                'stores' => Store::where('is_active', true)->count(),
                'employees' => Employee::where('is_active', true)->count(),
                'total_visits' => StoreVisit::count(),
                'visits_today' => StoreVisit::whereDate('visit_date', today())->count(),
                'pending_surveys' => QuestionAnswer::where('admin_status', 'pending')->count(),
                'pending_stock' => StockTransaction::where('status', 'pending')->count(),
                'active_visits' => StoreVisit::where('status', 'checked_in')->count(),
                'completed_visits_today' => StoreVisit::where('status', 'completed')->whereDate('visit_date', today())->count(),
                // ── NEW ──
                'flagged_stores' => StoreFlag::unresolved()->count(),
            ];

        } elseif ($user->hasRole('Zonal Head') && $employee && $employee->zone_id) {

            $stateIds = State::where('zone_id', $employee->zone_id)->pluck('id');
            $cityIds = City::whereIn('state_id', $stateIds)->pluck('id');
            $areaIds = Area::whereIn('city_id', $cityIds)->pluck('id');

            $stats = [
                'zone_name' => $employee->zone->name ?? 'N/A',
                'states' => State::where('zone_id', $employee->zone_id)->where('is_active', true)->count(),
                'cities' => City::whereIn('state_id', $stateIds)->where('is_active', true)->count(),
                'areas' => Area::whereIn('city_id', $cityIds)->where('is_active', true)->count(),
                'branches' => Branch::whereIn('state_id', $stateIds)->where('is_active', true)->count(),
                'stores' => Store::whereIn('state_id', $stateIds)->where('is_active', true)->count(),
                'employees' => Employee::where('zone_id', $employee->zone_id)->where('is_active', true)->count(),
                'total_visits' => StoreVisit::whereHas('store', fn($q) => $q->whereIn('state_id', $stateIds))->count(),
                'visits_today' => StoreVisit::whereHas('store', fn($q) => $q->whereIn('state_id', $stateIds))->whereDate('visit_date', today())->count(),
                'pending_surveys' => QuestionAnswer::whereHas('visit.store', fn($q) => $q->whereIn('state_id', $stateIds))->where('admin_status', 'pending')->count(),
                'pending_stock' => StockTransaction::whereHas('store', fn($q) => $q->whereIn('state_id', $stateIds))->where('status', 'pending')->count(),
                // ── NEW ──
                'flagged_stores' => StoreFlag::unresolved()->whereHas('store', fn($q) => $q->whereIn('state_id', $stateIds))->count(),
            ];

        } elseif ($user->hasRole('State Head') && $employee && $employee->state_id) {

            $cityIds = City::where('state_id', $employee->state_id)->pluck('id');
            $areaIds = Area::whereIn('city_id', $cityIds)->pluck('id');

            $stats = [
                'state_name' => $employee->state->name ?? 'N/A',
                'cities' => City::where('state_id', $employee->state_id)->where('is_active', true)->count(),
                'areas' => Area::whereIn('city_id', $cityIds)->where('is_active', true)->count(),
                'branches' => Branch::where('state_id', $employee->state_id)->where('is_active', true)->count(),
                'stores' => Store::where('state_id', $employee->state_id)->where('is_active', true)->count(),
                'employees' => Employee::where('state_id', $employee->state_id)->where('is_active', true)->count(),
                'total_visits' => StoreVisit::whereHas('store', fn($q) => $q->where('state_id', $employee->state_id))->count(),
                'visits_today' => StoreVisit::whereHas('store', fn($q) => $q->where('state_id', $employee->state_id))->whereDate('visit_date', today())->count(),
                'pending_surveys' => QuestionAnswer::whereHas('visit.store', fn($q) => $q->where('state_id', $employee->state_id))->where('admin_status', 'pending')->count(),
                'pending_stock' => StockTransaction::whereHas('store', fn($q) => $q->where('state_id', $employee->state_id))->where('status', 'pending')->count(),
                // ── NEW ──
                'flagged_stores' => StoreFlag::unresolved()->whereHas('store', fn($q) => $q->where('state_id', $employee->state_id))->count(),
            ];

        } elseif ($user->hasRole('City Head') && $employee && $employee->city_id) {

            $areaIds = Area::where('city_id', $employee->city_id)->pluck('id');

            $stats = [
                'city_name' => $employee->city->name ?? 'N/A',
                'areas' => Area::where('city_id', $employee->city_id)->where('is_active', true)->count(),
                'branches' => Branch::where('city_id', $employee->city_id)->where('is_active', true)->count(),
                'stores' => Store::where('city_id', $employee->city_id)->where('is_active', true)->count(),
                'employees' => Employee::where('city_id', $employee->city_id)->where('is_active', true)->count(),
                'total_visits' => StoreVisit::whereHas('store', fn($q) => $q->where('city_id', $employee->city_id))->count(),
                'visits_today' => StoreVisit::whereHas('store', fn($q) => $q->where('city_id', $employee->city_id))->whereDate('visit_date', today())->count(),
                'pending_surveys' => QuestionAnswer::whereHas('visit.store', fn($q) => $q->where('city_id', $employee->city_id))->where('admin_status', 'pending')->count(),
                'pending_stock' => StockTransaction::whereHas('store', fn($q) => $q->where('city_id', $employee->city_id))->where('status', 'pending')->count(),
                // ── NEW ──
                'flagged_stores' => StoreFlag::unresolved()->whereHas('store', fn($q) => $q->where('city_id', $employee->city_id))->count(),
            ];

        } elseif ($user->hasRole('On/Off Trade Head') && $employee && $employee->city_id) {

            $stats = [
                'city_name' => $employee->city->name ?? 'N/A',
                'stores' => Store::where('city_id', $employee->city_id)->where('is_active', true)->count(),
                'employees' => Employee::where('city_id', $employee->city_id)->where('is_active', true)->count(),
                'total_visits' => StoreVisit::whereHas('store', fn($q) => $q->where('city_id', $employee->city_id))->count(),
                'visits_today' => StoreVisit::whereHas('store', fn($q) => $q->where('city_id', $employee->city_id))->whereDate('visit_date', today())->count(),
                'pending_surveys' => QuestionAnswer::whereHas('visit.store', fn($q) => $q->where('city_id', $employee->city_id))->where('admin_status', 'pending')->count(),
                'pending_stock' => StockTransaction::whereHas('store', fn($q) => $q->where('city_id', $employee->city_id))->where('status', 'pending')->count(),
                // ── NEW ──
                'flagged_stores' => StoreFlag::unresolved()->whereHas('store', fn($q) => $q->where('city_id', $employee->city_id))->count(),
            ];

        } elseif ($user->hasRole('Sales Employee') && $employee) {

            $stats = [
                'assigned_stores' => $employee->assignedStores()->count(),
                'total_visits' => StoreVisit::where('employee_id', $employee->id)->count(),
                'visits_today' => StoreVisit::where('employee_id', $employee->id)->whereDate('visit_date', today())->count(),
                'completed_visits' => StoreVisit::where('employee_id', $employee->id)->where('status', 'completed')->count(),
                'active_visits' => StoreVisit::where('employee_id', $employee->id)->where('status', 'checked_in')->count(),
                'pending_surveys' => QuestionAnswer::whereHas('visit', fn($q) => $q->where('employee_id', $employee->id))->where('admin_status', 'pending')->count(),
                'approved_surveys' => QuestionAnswer::whereHas('visit', fn($q) => $q->where('employee_id', $employee->id))->where('admin_status', 'approved')->count(),
                'pending_stock' => StockTransaction::where('employee_id', $employee->id)->where('status', 'pending')->count(),
                'approved_stock' => StockTransaction::where('employee_id', $employee->id)->where('status', 'approved')->count(),
                // Sales employee sees their own flagged stores count
                'flagged_stores' => StoreFlag::where('employee_id', $employee->id)->unresolved()->count(),
            ];
        }

        return $stats;
    }

    // ─────────────────────────────────────────────────────────────────────────
    private function getRecentActivities($user, $employee)
    {
        $activities = [];

        $mapVisit = function ($visit) {
            return [
                'id' => $visit->id,
                'store_id' => $visit->store->id,
                'type' => 'visit',
                'employee_name' => $visit->employee?->name ?? null,
                'store_name' => $visit->store->name,
                'status' => $visit->status,
                'date' => $visit->visit_date,
                'time' => $visit->check_in_time,
                'created_at' => $visit->created_at,
                'breakage_count' => $visit->questionAnswers
                    ->first(fn($a) => $a->question?->isBreakage())
                        ?->count,
            ];
        };

        $with = ['employee.user', 'store', 'questionAnswers.question'];

        if ($user->hasRole(['Master Admin', 'Country Head'])) {

            $activities['recent_visits'] = StoreVisit::with($with)
                ->latest('created_at')->limit(5)->get()->map($mapVisit);

        } elseif ($user->hasRole('Zonal Head') && $employee && $employee->zone_id) {

            $stateIds = State::where('zone_id', $employee->zone_id)->pluck('id');
            $activities['recent_visits'] = StoreVisit::with($with)
                ->whereHas('store', fn($q) => $q->whereIn('state_id', $stateIds))
                ->latest('created_at')->limit(5)->get()->map($mapVisit);

        } elseif ($user->hasRole('State Head') && $employee && $employee->state_id) {

            $activities['recent_visits'] = StoreVisit::with($with)
                ->whereHas('store', fn($q) => $q->where('state_id', $employee->state_id))
                ->latest('created_at')->limit(5)->get()->map($mapVisit);

        } elseif ($user->hasRole(['City Head', 'On/Off Trade Head']) && $employee && $employee->city_id) {

            $activities['recent_visits'] = StoreVisit::with($with)
                ->whereHas('store', fn($q) => $q->where('city_id', $employee->city_id))
                ->latest('created_at')->limit(5)->get()->map($mapVisit);

        } elseif ($user->hasRole('Sales Employee') && $employee) {

            $activities['recent_visits'] = StoreVisit::with($with)
                ->where('employee_id', $employee->id)
                ->latest('created_at')->limit(5)->get()->map($mapVisit);
        }

        // ── NEW: Flagged stores — scoped by role via model scope ──────────
        $activities['flagged_stores'] = StoreFlag::unresolved()
            ->forCurrentUser()
            ->with([
                'store:id,name,city_id,state_id',
                'store.city:id,name',
                'store.state:id,name',
                'employee:id,name',
            ])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($flag) => [
                'flag_id' => $flag->id,
                'store_id' => $flag->store_id,
                'store_name' => $flag->store->name,
                'city' => $flag->store->city?->name,
                'state' => $flag->store->state?->name,
                'employee' => $flag->employee->name,
                'flag_note' => $flag->flag_note,
                'flagged_at' => $flag->created_at->toDateTimeString(),
            ]);

        return $activities;
    }

    // ─────────────────────────────────────────────────────────────────────────
    private function getHierarchyBreakdown($user, $employee)
    {
        $breakdown = [];

        if ($user->hasRole(['Master Admin', 'Country Head'])) {

            $breakdown['zones'] = Zone::where('is_active', true)
                ->withCount(['states', 'employees'])
                ->get()
                ->map(function ($zone) {
                    $stateIds = $zone->states->pluck('id');
                    return [
                        'name' => $zone->name,
                        'states_count' => $zone->states_count,
                        'cities_count' => City::whereIn('state_id', $stateIds)->count(),
                        'stores_count' => Store::whereIn('state_id', $stateIds)->count(),
                        'employees_count' => $zone->employees_count,
                    ];
                });

        } elseif ($user->hasRole('Zonal Head') && $employee && $employee->zone_id) {

            $breakdown['states'] = State::where('zone_id', $employee->zone_id)
                ->where('is_active', true)
                ->withCount(['cities', 'employees'])
                ->get()
                ->map(fn($state) => [
                    'name' => $state->name,
                    'cities_count' => $state->cities_count,
                    'stores_count' => Store::where('state_id', $state->id)->count(),
                    'employees_count' => $state->employees_count,
                ]);

        } elseif ($user->hasRole('State Head') && $employee && $employee->state_id) {

            $breakdown['cities'] = City::where('state_id', $employee->state_id)
                ->where('is_active', true)
                ->withCount(['areas', 'employees'])
                ->get()
                ->map(fn($city) => [
                    'name' => $city->name,
                    'areas_count' => $city->areas_count,
                    'stores_count' => Store::where('city_id', $city->id)->count(),
                    'employees_count' => $city->employees_count,
                ]);

        } elseif ($user->hasRole('City Head') && $employee && $employee->city_id) {

            $breakdown['areas'] = Area::where('city_id', $employee->city_id)
                ->where('is_active', true)
                ->withCount(['employees'])
                ->get()
                ->map(fn($area) => [
                    'name' => $area->name,
                    'stores_count' => Store::where('area_id', $area->id)->count(),
                    'employees_count' => $area->employees_count,
                ]);
        }

        return $breakdown;
    }
}
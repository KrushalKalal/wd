<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Zone;
use App\Models\State;
use App\Models\City;
use App\Models\Area;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\Employee;
use App\Models\StoreVisit;
use App\Models\StockTransaction;
use App\Models\QuestionAnswer;
use App\Helpers\RoleAccessHelper;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $role = $user->getRoleNames()->first();
        $employee = $user->employee;

        // Get role-based statistics
        $statistics = $this->getRoleBasedStatistics($user, $employee);

        // Get recent activities (latest 5 entries)
        $recentActivities = $this->getRecentActivities($user, $employee);

        // Get hierarchy breakdown
        $hierarchyBreakdown = $this->getHierarchyBreakdown($user, $employee);

        return Inertia::render('Dashboard', [
            'role' => $role,
            'statistics' => $statistics,
            'recentActivities' => $recentActivities,
            'hierarchyBreakdown' => $hierarchyBreakdown,
        ]);
    }

    private function getRoleBasedStatistics($user, $employee)
    {
        $stats = [];

        if ($user->hasRole(['Master Admin', 'Country Head'])) {
            // Full system statistics
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
                'completed_visits_today' => StoreVisit::where('status', 'completed')
                    ->whereDate('visit_date', today())
                    ->count(),
            ];
        } elseif ($user->hasRole('Zonal Head') && $employee && $employee->zone_id) {
            // Zone-level statistics
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
                'total_visits' => StoreVisit::whereHas('store', function ($q) use ($stateIds) {
                    $q->whereIn('state_id', $stateIds);
                })->count(),
                'visits_today' => StoreVisit::whereHas('store', function ($q) use ($stateIds) {
                    $q->whereIn('state_id', $stateIds);
                })->whereDate('visit_date', today())->count(),
                'pending_surveys' => QuestionAnswer::whereHas('visit.store', function ($q) use ($stateIds) {
                    $q->whereIn('state_id', $stateIds);
                })->where('admin_status', 'pending')->count(),
                'pending_stock' => StockTransaction::whereHas('store', function ($q) use ($stateIds) {
                    $q->whereIn('state_id', $stateIds);
                })->where('status', 'pending')->count(),
            ];
        } elseif ($user->hasRole('State Head') && $employee && $employee->state_id) {
            // State-level statistics
            $cityIds = City::where('state_id', $employee->state_id)->pluck('id');
            $areaIds = Area::whereIn('city_id', $cityIds)->pluck('id');

            $stats = [
                'state_name' => $employee->state->name ?? 'N/A',
                'cities' => City::where('state_id', $employee->state_id)->where('is_active', true)->count(),
                'areas' => Area::whereIn('city_id', $cityIds)->where('is_active', true)->count(),
                'branches' => Branch::where('state_id', $employee->state_id)->where('is_active', true)->count(),
                'stores' => Store::where('state_id', $employee->state_id)->where('is_active', true)->count(),
                'employees' => Employee::where('state_id', $employee->state_id)->where('is_active', true)->count(),
                'total_visits' => StoreVisit::whereHas('store', function ($q) use ($employee) {
                    $q->where('state_id', $employee->state_id);
                })->count(),
                'visits_today' => StoreVisit::whereHas('store', function ($q) use ($employee) {
                    $q->where('state_id', $employee->state_id);
                })->whereDate('visit_date', today())->count(),
                'pending_surveys' => QuestionAnswer::whereHas('visit.store', function ($q) use ($employee) {
                    $q->where('state_id', $employee->state_id);
                })->where('admin_status', 'pending')->count(),
                'pending_stock' => StockTransaction::whereHas('store', function ($q) use ($employee) {
                    $q->where('state_id', $employee->state_id);
                })->where('status', 'pending')->count(),
            ];
        } elseif ($user->hasRole('City Head') && $employee && $employee->city_id) {
            // City-level statistics
            $areaIds = Area::where('city_id', $employee->city_id)->pluck('id');

            $stats = [
                'city_name' => $employee->city->name ?? 'N/A',
                'areas' => Area::where('city_id', $employee->city_id)->where('is_active', true)->count(),
                'branches' => Branch::where('city_id', $employee->city_id)->where('is_active', true)->count(),
                'stores' => Store::where('city_id', $employee->city_id)->where('is_active', true)->count(),
                'employees' => Employee::where('city_id', $employee->city_id)->where('is_active', true)->count(),
                'total_visits' => StoreVisit::whereHas('store', function ($q) use ($employee) {
                    $q->where('city_id', $employee->city_id);
                })->count(),
                'visits_today' => StoreVisit::whereHas('store', function ($q) use ($employee) {
                    $q->where('city_id', $employee->city_id);
                })->whereDate('visit_date', today())->count(),
                'pending_surveys' => QuestionAnswer::whereHas('visit.store', function ($q) use ($employee) {
                    $q->where('city_id', $employee->city_id);
                })->where('admin_status', 'pending')->count(),
                'pending_stock' => StockTransaction::whereHas('store', function ($q) use ($employee) {
                    $q->where('city_id', $employee->city_id);
                })->where('status', 'pending')->count(),
            ];
        } elseif ($user->hasRole('Sales Employee') && $employee) {
            // Sales employee statistics (own data only)
            $stats = [
                'assigned_stores' => $employee->assignedStores()->count(),
                'total_visits' => StoreVisit::where('employee_id', $employee->id)->count(),
                'visits_today' => StoreVisit::where('employee_id', $employee->id)
                    ->whereDate('visit_date', today())
                    ->count(),
                'completed_visits' => StoreVisit::where('employee_id', $employee->id)
                    ->where('status', 'completed')
                    ->count(),
                'active_visits' => StoreVisit::where('employee_id', $employee->id)
                    ->where('status', 'checked_in')
                    ->count(),
                'pending_surveys' => QuestionAnswer::whereHas('visit', function ($q) use ($employee) {
                    $q->where('employee_id', $employee->id);
                })->where('admin_status', 'pending')->count(),
                'approved_surveys' => QuestionAnswer::whereHas('visit', function ($q) use ($employee) {
                    $q->where('employee_id', $employee->id);
                })->where('admin_status', 'approved')->count(),
                'pending_stock' => StockTransaction::where('employee_id', $employee->id)
                    ->where('status', 'pending')
                    ->count(),
                'approved_stock' => StockTransaction::where('employee_id', $employee->id)
                    ->where('status', 'approved')
                    ->count(),
            ];
        }

        return $stats;
    }

    private function getRecentActivities($user, $employee)
    {
        $activities = [];

        if ($user->hasRole(['Master Admin', 'Country Head'])) {
            // Get latest 5 store visits
            $activities['recent_visits'] = StoreVisit::with(['employee.user', 'store'])
                ->latest('created_at')
                ->limit(5)
                ->get()
                ->map(function ($visit) {
                    return [
                        'id' => $visit->id,
                        'type' => 'visit',
                        'employee_name' => $visit->employee->name,
                        'store_name' => $visit->store->name,
                        'status' => $visit->status,
                        'date' => $visit->visit_date,
                        'time' => $visit->check_in_time,
                        'created_at' => $visit->created_at,
                    ];
                });
        } elseif ($user->hasRole('Zonal Head') && $employee && $employee->zone_id) {
            $stateIds = State::where('zone_id', $employee->zone_id)->pluck('id');

            $activities['recent_visits'] = StoreVisit::with(['employee.user', 'store'])
                ->whereHas('store', function ($q) use ($stateIds) {
                    $q->whereIn('state_id', $stateIds);
                })
                ->latest('created_at')
                ->limit(5)
                ->get()
                ->map(function ($visit) {
                    return [
                        'id' => $visit->id,
                        'type' => 'visit',
                        'employee_name' => $visit->employee->name,
                        'store_name' => $visit->store->name,
                        'status' => $visit->status,
                        'date' => $visit->visit_date,
                        'time' => $visit->check_in_time,
                        'created_at' => $visit->created_at,
                    ];
                });
        } elseif ($user->hasRole('State Head') && $employee && $employee->state_id) {
            $activities['recent_visits'] = StoreVisit::with(['employee.user', 'store'])
                ->whereHas('store', function ($q) use ($employee) {
                    $q->where('state_id', $employee->state_id);
                })
                ->latest('created_at')
                ->limit(5)
                ->get()
                ->map(function ($visit) {
                    return [
                        'id' => $visit->id,
                        'type' => 'visit',
                        'employee_name' => $visit->employee->name,
                        'store_name' => $visit->store->name,
                        'status' => $visit->status,
                        'date' => $visit->visit_date,
                        'time' => $visit->check_in_time,
                        'created_at' => $visit->created_at,
                    ];
                });
        } elseif ($user->hasRole('City Head') && $employee && $employee->city_id) {
            $activities['recent_visits'] = StoreVisit::with(['employee.user', 'store'])
                ->whereHas('store', function ($q) use ($employee) {
                    $q->where('city_id', $employee->city_id);
                })
                ->latest('created_at')
                ->limit(5)
                ->get()
                ->map(function ($visit) {
                    return [
                        'id' => $visit->id,
                        'type' => 'visit',
                        'employee_name' => $visit->employee->name,
                        'store_name' => $visit->store->name,
                        'status' => $visit->status,
                        'date' => $visit->visit_date,
                        'time' => $visit->check_in_time,
                        'created_at' => $visit->created_at,
                    ];
                });
        } elseif ($user->hasRole('Sales Employee') && $employee) {
            $activities['recent_visits'] = StoreVisit::with(['store'])
                ->where('employee_id', $employee->id)
                ->latest('created_at')
                ->limit(5)
                ->get()
                ->map(function ($visit) {
                    return [
                        'id' => $visit->id,
                        'type' => 'visit',
                        'store_name' => $visit->store->name,
                        'status' => $visit->status,
                        'date' => $visit->visit_date,
                        'time' => $visit->check_in_time,
                        'created_at' => $visit->created_at,
                    ];
                });
        }

        return $activities;
    }

    private function getHierarchyBreakdown($user, $employee)
    {
        $breakdown = [];

        if ($user->hasRole(['Master Admin', 'Country Head'])) {
            // Zone-wise breakdown
            $breakdown['zones'] = Zone::where('is_active', true)
                ->withCount(['states', 'employees'])
                ->get()
                ->map(function ($zone) {
                    $stateIds = $zone->states->pluck('id');
                    $cityIds = City::whereIn('state_id', $stateIds)->pluck('id');

                    return [
                        'name' => $zone->name,
                        'states_count' => $zone->states_count,
                        'cities_count' => City::whereIn('state_id', $stateIds)->count(),
                        'stores_count' => Store::whereIn('state_id', $stateIds)->count(),
                        'employees_count' => $zone->employees_count,
                    ];
                });
        } elseif ($user->hasRole('Zonal Head') && $employee && $employee->zone_id) {
            // State-wise breakdown within zone
            $breakdown['states'] = State::where('zone_id', $employee->zone_id)
                ->where('is_active', true)
                ->withCount(['cities', 'employees'])
                ->get()
                ->map(function ($state) {
                    return [
                        'name' => $state->name,
                        'cities_count' => $state->cities_count,
                        'stores_count' => Store::where('state_id', $state->id)->count(),
                        'employees_count' => $state->employees_count,
                    ];
                });
        } elseif ($user->hasRole('State Head') && $employee && $employee->state_id) {
            // City-wise breakdown within state
            $breakdown['cities'] = City::where('state_id', $employee->state_id)
                ->where('is_active', true)
                ->withCount(['areas', 'employees'])
                ->get()
                ->map(function ($city) {
                    return [
                        'name' => $city->name,
                        'areas_count' => $city->areas_count,
                        'stores_count' => Store::where('city_id', $city->id)->count(),
                        'employees_count' => $city->employees_count,
                    ];
                });
        } elseif ($user->hasRole('City Head') && $employee && $employee->city_id) {
            // Area-wise breakdown within city
            $breakdown['areas'] = Area::where('city_id', $employee->city_id)
                ->where('is_active', true)
                ->withCount(['employees'])
                ->get()
                ->map(function ($area) {
                    return [
                        'name' => $area->name,
                        'stores_count' => Store::where('area_id', $area->id)->count(),
                        'employees_count' => $area->employees_count,
                    ];
                });
        }

        return $breakdown;
    }
}
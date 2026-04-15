<?php

namespace App\Http\Controllers;

use App\Helpers\RoleAccessHelper;
use App\Models\DailyPlan;
use App\Models\Employee;
use App\Models\State;
use App\Models\StoreVisit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class EmployeeManagementController extends Controller
{
    /**
     * GET /employee-management
     *
     * Employee list scoped by logged-in user's role (same as all other modules).
     * Each row shows today's plan summary alongside standard employee info.
     */
    public function index(Request $request)
    {
        $query = Employee::with([
            'user.roles',
            'zone',
            'state',
            'city',
            'area',
            'manager',
        ]);

        // ── ONLY Sales Employees ──────────────────────────────────────────
        $query->whereHas('user.roles', function ($q) {
            $q->where('name', 'Sales Employee');
        });

        // Scope by role — same helper used everywhere
        $query = RoleAccessHelper::applyRoleFilter($query);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%")
                    ->orWhere('contact_number_1', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($request->filled('state_id')) {
            $query->where('state_id', $request->state_id);
        }
        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        $perPage = $request->get('per_page', 15);
        $employees = $query->where('is_active', true)->orderBy('name')->paginate($perPage);

        // Get employee IDs for bulk queries
        $empIds = $employees->pluck('id');

        // Today's plan summary per employee — single query
        $todayPlans = DailyPlan::whereIn('employee_id', $empIds)
            ->whereDate('plan_date', today())
            ->withCount([
                'planStores',
                'planStores as visited_count' => fn($q) => $q->where('status', 'visited'),
                'planStores as pending_count' => fn($q) => $q->where('status', 'pending'),
                'planStores as skipped_count' => fn($q) => $q->where('status', 'skipped'),
            ])
            ->get()
            ->keyBy('employee_id');

        // Today's visit count per employee
        $todayVisitCounts = StoreVisit::whereIn('employee_id', $empIds)
            ->whereDate('visit_date', today())
            ->selectRaw('employee_id, COUNT(*) as total, SUM(check_out_time IS NOT NULL) as completed')
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        $employees->getCollection()->transform(function ($emp) use ($todayPlans, $todayVisitCounts) {
            $plan = $todayPlans->get($emp->id);
            $visitStats = $todayVisitCounts->get($emp->id);

            $emp->role_name = $emp->user->roles->first()?->name ?? '—';
            $emp->manager_name = $emp->manager?->name ?? '—';

            // Today plan info
            $emp->today_plan = $plan ? [
                'id' => $plan->id,
                'day_started' => $plan->day_start_time !== null,
                'day_ended' => $plan->day_end_time !== null,
                'day_start_time' => $plan->day_start_time,
                'day_end_time' => $plan->day_end_time,
                'stores_planned' => $plan->plan_stores_count,
                'stores_visited' => $plan->visited_count,
                'stores_pending' => $plan->pending_count,
                'stores_skipped' => $plan->skipped_count,
            ] : null;

            $emp->today_visits_total = $visitStats?->total ?? 0;
            $emp->today_visits_completed = $visitStats?->completed ?? 0;

            return $emp;
        });

        // Filter dropdowns — scoped
        $stateIds = RoleAccessHelper::getAccessibleStateIds();
        $states = State::whereIn('id', $stateIds)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        // Summary stats for header cards
        $allEmpIds = Employee::where('is_active', true)
            ->when(!empty($stateIds), fn($q) => $q->whereIn('state_id', $stateIds))
            ->pluck('id');

        $statistics = [
            'total_employees' => $allEmpIds->count(),
            'active_today' => DailyPlan::whereIn('employee_id', $allEmpIds)
                ->whereDate('plan_date', today())
                ->whereNotNull('day_start_time')
                ->count(),
            'visits_today' => StoreVisit::whereIn('employee_id', $allEmpIds)
                ->whereDate('visit_date', today())
                ->count(),
            'completed_today' => StoreVisit::whereIn('employee_id', $allEmpIds)
                ->whereDate('visit_date', today())
                ->where('status', 'completed')
                ->count(),
        ];

        return Inertia::render('EmployeeManagement/Index', [
            'records' => $employees,
            'states' => $states,
            'statistics' => $statistics,
            'filters' => [
                'search' => $request->search,
                'state_id' => $request->state_id,
                'city_id' => $request->city_id,
                'area_id' => $request->area_id,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * GET /employee-management/{id}?date=2025-01-15
     *
     * Employee detail page — shows plan + full route timeline + map for a given date.
     * Date defaults to today.
     */
    public function show(Request $request, $employeeId)
    {
        $employee = Employee::with([
            'user.roles',
            'zone',
            'state',
            'city',
            'area',
            'manager',
        ])->findOrFail($employeeId);

        $date = $request->get('date', today()->toDateString());

        // Validate date format
        try {
            $carbon = Carbon::parse($date);
        } catch (\Exception $e) {
            $carbon = today();
            $date = $carbon->toDateString();
        }

        // Daily plan for this date
        $plan = DailyPlan::where('employee_id', $employeeId)
            ->whereDate('plan_date', $date)
            ->with([
                'planStores' => fn($q) => $q->orderBy('visit_order')->with([
                    'store:id,name,address,latitude,longitude',
                    'visit' => fn($q) => $q->with([
                        'orders:id,visit_id,total_amount,status',
                        'stockTransactions:id,visit_id,status,type',
                        'questionAnswers:id,visit_id,admin_status',
                    ]),
                ]),
                'remarkBy:id,name',
            ])
            ->first();

        // All store visits for this employee on this date (includes walk-ins)
        $visits = StoreVisit::where('employee_id', $employeeId)
            ->whereDate('visit_date', $date)
            ->with([
                'store:id,name,address,latitude,longitude',
                'orders:id,visit_id,total_amount,status',
                'stockTransactions:id,visit_id,status,type',
                'questionAnswers:id,visit_id,admin_status',
            ])
            ->orderBy('check_in_time')
            ->get()
            ->map(function ($visit) {
                // Duration in minutes
                $visit->duration_minutes = null;
                if ($visit->check_in_time && $visit->check_out_time) {
                    $visit->duration_minutes = Carbon::parse($visit->check_in_time)
                        ->diffInMinutes(Carbon::parse($visit->check_out_time));
                }

                // Quick stats
                $visit->orders_count = $visit->orders->count();
                $visit->orders_total = $visit->orders->sum('total_amount');
                $visit->stock_count = $visit->stockTransactions->count();
                $visit->survey_count = $visit->questionAnswers->count();
                $visit->is_planned = $visit->daily_plan_store_id !== null;

                return $visit;
            });

        // Build route points array for the Google Maps component in JSX
        // Format: [{type, label, lat, lng, time, meta}]
        $routePoints = [];

        // 1. Day start
        if ($plan && $plan->start_lat) {
            $routePoints[] = [
                'type' => 'day_start',
                'label' => 'Day start',
                'lat' => (float) $plan->start_lat,
                'lng' => (float) $plan->start_lng,
                'time' => $plan->day_start_time,
                'meta' => null,
            ];
        }

        // 2. Each visit in check-in time order
        foreach ($visits as $visit) {
            if (!$visit->latitude || !$visit->longitude) {
                continue;
            }
            $routePoints[] = [
                'type' => $visit->is_planned ? 'planned_visit' : 'walkin_visit',
                'label' => $visit->store->name,
                'lat' => (float) $visit->latitude,
                'lng' => (float) $visit->longitude,
                'time' => $visit->check_in_time,
                'checkout' => $visit->check_out_time,
                'duration' => $visit->duration_minutes,
                'visit_id' => $visit->id,
                'store_id' => $visit->store_id,
                'meta' => [
                    'orders_count' => $visit->orders_count,
                    'orders_total' => $visit->orders_total,
                    'stock_count' => $visit->stock_count,
                    'survey_count' => $visit->survey_count,
                    'status' => $visit->status,
                ],
            ];
        }

        // 3. Skipped planned stores (use store lat/lng as placeholder)
        if ($plan) {
            foreach ($plan->planStores->where('status', 'skipped') as $ps) {
                if (!$ps->store->latitude || !$ps->store->longitude) {
                    continue;
                }
                $routePoints[] = [
                    'type' => 'skipped',
                    'label' => $ps->store->name,
                    'lat' => (float) $ps->store->latitude,
                    'lng' => (float) $ps->store->longitude,
                    'time' => $ps->planned_time,
                    'meta' => ['visit_order' => $ps->visit_order],
                ];
            }
        }

        // 4. Day end
        if ($plan && $plan->end_lat) {
            $routePoints[] = [
                'type' => 'day_end',
                'label' => 'Day end',
                'lat' => (float) $plan->end_lat,
                'lng' => (float) $plan->end_lng,
                'time' => $plan->day_end_time,
                'meta' => null,
            ];
        }

        // Previous 30 dates that have data — for the date picker dropdown
        $availableDates = DailyPlan::where('employee_id', $employeeId)
            ->orderBy('plan_date', 'desc')
            ->limit(30)
            ->pluck('plan_date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        // Also add dates that have visits but no plan
        $visitDates = StoreVisit::where('employee_id', $employeeId)
            ->selectRaw('DATE(visit_date) as d')
            ->groupBy('d')
            ->orderBy('d', 'desc')
            ->limit(30)
            ->pluck('d')
            ->toArray();

        $allDates = collect(array_unique(array_merge($availableDates, $visitDates)))
            ->sortDesc()
            ->values()
            ->toArray();

        return Inertia::render('EmployeeManagement/Show', [
            'employee' => $employee,
            'plan' => $plan,
            'visits' => $visits,
            'route_points' => $routePoints,
            'selected_date' => $date,
            'available_dates' => $allDates,
        ]);
    }

    /**
     * POST /employee-management/plans/{planId}/remark
     *
     * Manager adds a remark on an employee's daily plan.
     */
    public function addRemark(Request $request, $planId)
    {
        $request->validate([
            'manager_remark' => 'required|string|max:1000',
        ]);

        try {
            $plan = DailyPlan::findOrFail($planId);

            $managerEmployee = auth()->user()->employee;

            $plan->update([
                'manager_remark' => $request->manager_remark,
                'remark_by' => $managerEmployee?->id,
                'remark_at' => now(),
            ]);

            return back()->with('success', 'Remark saved successfully.');

        } catch (\Exception $e) {
            Log::error('Employee management remark failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to save remark.');
        }
    }
}
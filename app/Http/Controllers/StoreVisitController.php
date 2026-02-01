<?php

namespace App\Http\Controllers;

use App\Models\StoreVisit;
use App\Models\Employee;
use App\Models\Store;
use App\Models\QuestionAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class StoreVisitController extends Controller
{
    public function index(Request $request)
    {
        $query = StoreVisit::with([
            'employee.user',
            'store.state',
            'store.city',
            'store.area',
            'questionAnswers.question',
            'stockTransactions.product'
        ]);

        // Filter by status
        $status = $request->get('status', 'all');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Filter by employee
        if ($request->has('employee_id') && $request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by store
        if ($request->has('store_id') && $request->store_id) {
            $query->where('store_id', $request->store_id);
        }

        // Filter by date range
        if ($request->has('from_date') && $request->from_date) {
            $query->whereDate('visit_date', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date) {
            $query->whereDate('visit_date', '<=', $request->to_date);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('employee', function ($empQuery) use ($request) {
                    $empQuery->where('name', 'like', '%' . $request->search . '%');
                })->orWhereHas('store', function ($storeQuery) use ($request) {
                    $storeQuery->where('name', 'like', '%' . $request->search . '%');
                });
            });
        }

        $perPage = $request->get('per_page', 15);
        $visits = $query->orderBy('visit_date', 'desc')
            ->orderBy('check_in_time', 'desc')
            ->paginate($perPage);

        // Add computed fields
        $visits->getCollection()->transform(function ($visit) {
            $visit->duration_minutes = null;
            if ($visit->check_in_time && $visit->check_out_time) {
                $checkIn = \Carbon\Carbon::parse($visit->check_in_time);
                $checkOut = \Carbon\Carbon::parse($visit->check_out_time);
                $visit->duration_minutes = $checkIn->diffInMinutes($checkOut);
            }

            $visit->survey_count = $visit->questionAnswers->count();
            $visit->stock_transactions_count = $visit->stockTransactions->count();

            return $visit;
        });

        // Get filter data
        $employees = Employee::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $stores = Store::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        // Get counts for status tabs
        $statusCounts = [
            'all' => StoreVisit::count(),
            'checked_in' => StoreVisit::where('status', 'checked_in')->count(),
            'completed' => StoreVisit::where('status', 'completed')->count(),
        ];

        return Inertia::render('StoreVisits/Index', [
            'records' => $visits,
            'employees' => $employees,
            'stores' => $stores,
            'statusCounts' => $statusCounts,
            'filters' => [
                'search' => $request->search,
                'status' => $status,
                'employee_id' => $request->employee_id,
                'store_id' => $request->store_id,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function show($id)
    {
        $visit = StoreVisit::with([
            'employee.user',
            'employee.manager',
            'store.state',
            'store.city',
            'store.area',
            'questionAnswers.question',
            'stockTransactions.product'
        ])->findOrFail($id);

        // Calculate duration
        $visit->duration_minutes = null;
        if ($visit->check_in_time && $visit->check_out_time) {
            $checkIn = \Carbon\Carbon::parse($visit->check_in_time);
            $checkOut = \Carbon\Carbon::parse($visit->check_out_time);
            $visit->duration_minutes = $checkIn->diffInMinutes($checkOut);
        }

        return Inertia::render('StoreVisits/Details', [
            'visit' => $visit,
        ]);
    }

    public function updateSurveyStatus(Request $request, $answerId)
    {
        $request->validate([
            'admin_status' => 'required|in:approved,rejected,needs_review',
            'admin_remark' => 'nullable|string|max:500',
        ]);

        try {
            $answer = QuestionAnswer::findOrFail($answerId);

            // Add admin_status and admin_remark columns to question_answers table if not exists
            $answer->admin_status = $request->admin_status;
            $answer->admin_remark = $request->admin_remark;
            $answer->reviewed_by = auth()->id();
            $answer->reviewed_at = now();
            $answer->save();

            return response()->json([
                'success' => true,
                'message' => 'Survey answer status updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Survey status update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update survey status'
            ], 500);
        }
    }

    public function statistics(Request $request)
    {
        $stats = [
            'total_visits_today' => StoreVisit::whereDate('visit_date', today())->count(),
            'active_visits' => StoreVisit::where('status', 'checked_in')->count(),
            'completed_today' => StoreVisit::where('status', 'completed')
                ->whereDate('visit_date', today())
                ->count(),
            'total_visits_month' => StoreVisit::whereMonth('visit_date', now()->month)
                ->whereYear('visit_date', now()->year)
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
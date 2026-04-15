<?php

namespace App\Http\Controllers;

use App\Models\StoreFlag;
use App\Models\State;
use App\Helpers\RoleAccessHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class StoreFlagController extends Controller
{
    /**
     * GET /flagged-stores
     * All unresolved flags scoped by logged-in user's role.
     */
    public function index(Request $request)
    {
        $query = StoreFlag::unresolved()
            ->forCurrentUser()
            ->with([
                'store:id,name,address,city_id,state_id,area_id',
                'store.city:id,name',
                'store.state:id,name',
                'store.area:id,name',
                'employee:id,name,designation',
                'visit:id,visit_date,check_in_time',
            ]);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas(
                    'store',
                    fn($sq) =>
                    $sq->where('name', 'like', "%{$search}%")
                )->orWhereHas(
                        'employee',
                        fn($eq) =>
                        $eq->where('name', 'like', "%{$search}%")
                    )->orWhere('flag_note', 'like', "%{$search}%");
            });
        }

        // Filter by state
        if ($request->filled('state_id')) {
            $query->whereHas(
                'store',
                fn($q) =>
                $q->where('state_id', $request->state_id)
            );
        }

        $perPage = $request->get('per_page', 15);
        $flags = $query->orderByDesc('created_at')->paginate($perPage);

        // States for filter dropdown (scoped by role)
        $stateIds = RoleAccessHelper::getAccessibleStateIds();
        $states = State::whereIn('id', $stateIds)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('FlaggedStores/Index', [
            'records' => $flags,
            'states' => $states,
            'filters' => [
                'search' => $request->search,
                'state_id' => $request->state_id,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * POST /flagged-stores/{id}/resolve
     * Mark a flag as resolved from web.
     */
    public function resolve(Request $request, $id)
    {
        $request->validate([
            'resolved_note' => 'nullable|string|max:500',
        ]);

        try {
            $flag = StoreFlag::forCurrentUser()
                ->unresolved()
                ->findOrFail($id);

            $flag->update([
                'is_resolved' => true,
                'resolved_by' => auth()->id(),
                'resolved_at' => now(),
                'resolved_note' => $request->resolved_note,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Flag resolved successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Flag resolve failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to resolve flag.',
            ], 500);
        }
    }

    /**
     * GET /flagged-stores/count
     * Polling endpoint — returns unresolved flag count for header badge.
     * Called every 60s by the web header.
     */
    public function count()
    {
        $count = StoreFlag::unresolved()
            ->forCurrentUser()
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }
}
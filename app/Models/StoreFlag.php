<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreFlag extends Model
{
    protected $fillable = [
        'employee_id',
        'store_id',
        'visit_id',
        'flag_note',
        'is_resolved',
        'resolved_by',
        'resolved_at',
        'resolved_note',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function visit()
    {
        return $this->belongsTo(StoreVisit::class, 'visit_id');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    /**
     * Scope by role — same logic as RoleAccessHelper but for store_flags.
     * Filters flags based on the store's location (state/city/zone).
     */
    public static function scopeForCurrentUser($query)
    {
        $user = auth()->user();
        $employee = $user->employee;

        if ($user->hasRole(['Master Admin', 'Country Head'])) {
            return $query; // see all
        }

        if ($user->hasRole('Zonal Head') && $employee?->zone_id) {
            $stateIds = State::where('zone_id', $employee->zone_id)->pluck('id');
            return $query->whereHas('store', fn($q) => $q->whereIn('state_id', $stateIds));
        }

        if ($user->hasRole('State Head') && $employee?->state_id) {
            return $query->whereHas('store', fn($q) => $q->where('state_id', $employee->state_id));
        }

        if ($user->hasRole(['City Head', 'On/Off Trade Head']) && $employee?->city_id) {
            return $query->whereHas('store', fn($q) => $q->where('city_id', $employee->city_id));
        }

        // Sales Employee — should not see web flags page, return empty
        return $query->whereRaw('1 = 0');
    }
}
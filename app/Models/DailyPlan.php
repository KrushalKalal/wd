<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyPlan extends Model
{
    protected $fillable = [
        'employee_id',
        'plan_date',
        'notes',
        'manager_remark',
        'remark_by',
        'remark_at',
        'day_start_time',
        'start_lat',
        'start_lng',
        'day_end_time',
        'end_lat',
        'end_lng',
        'is_active',
    ];

    protected $casts = [
        'plan_date' => 'date',
        'is_active' => 'boolean',
        'remark_at' => 'datetime',
        'start_lat' => 'float',
        'start_lng' => 'float',
        'end_lat' => 'float',
        'end_lng' => 'float',
    ];

    // ─── Relations ────────────────────────────────────────────────

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Ordered list of stores in this plan
     */
    public function planStores()
    {
        return $this->hasMany(DailyPlanStore::class)->orderBy('visit_order');
    }

    /**
     * Manager who added remark
     */
    public function remarkBy()
    {
        return $this->belongsTo(Employee::class, 'remark_by');
    }

    // ─── Helpers ──────────────────────────────────────────────────

    public function isDayStarted(): bool
    {
        return $this->day_start_time !== null;
    }

    public function isDayEnded(): bool
    {
        return $this->day_end_time !== null;
    }

    public function visitedCount(): int
    {
        return $this->planStores()->where('status', 'visited')->count();
    }

    public function pendingCount(): int
    {
        return $this->planStores()->where('status', 'pending')->count();
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('plan_date', today());
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('plan_date', $date);
    }
}
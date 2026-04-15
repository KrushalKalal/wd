<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreVisit extends Model
{
    protected $fillable = [
        'is_active',
        'employee_id',
        'store_id',
        'visit_date',
        'check_in_time',
        'check_out_time',
        'latitude',
        'longitude',
        'status',
        'visit_summary',
        'daily_plan_store_id',   // NEW — null = walk-in, set = planned visit
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'visit_date' => 'date',
    ];

    // ─── Existing relations (unchanged) ───────────────────────────

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function questionAnswers()
    {
        return $this->hasMany(QuestionAnswer::class, 'visit_id');
    }

    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class, 'visit_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'visit_id');
    }

    // ─── NEW: Daily plan relation ──────────────────────────────────

    /**
     * The daily plan store entry this visit belongs to.
     * Null when the visit is a walk-in (unplanned).
     */
    public function dailyPlanStore()
    {
        return $this->belongsTo(DailyPlanStore::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    /**
     * Was this visit part of a daily plan?
     */
    public function isPlanned(): bool
    {
        return $this->daily_plan_store_id !== null;
    }

    // ─── Scopes (unchanged) ───────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('visit_date', today());
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyPlanStore extends Model
{
    protected $fillable = [
        'daily_plan_id',
        'store_id',
        'visit_order',
        'planned_time',
        'status',
    ];

    protected $casts = [
        'visit_order' => 'integer',
    ];

    // ─── Relations ────────────────────────────────────────────────

    public function dailyPlan()
    {
        return $this->belongsTo(DailyPlan::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * The actual store visit linked to this plan entry.
     * Null if the store was skipped or not yet visited.
     */
    public function visit()
    {
        return $this->hasOne(StoreVisit::class, 'daily_plan_store_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeVisited($query)
    {
        return $query->where('status', 'visited');
    }

    public function scopeSkipped($query)
    {
        return $query->where('status', 'skipped');
    }
}
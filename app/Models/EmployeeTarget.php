<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeTarget extends Model
{
    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'visit_target',
        'visits_completed',
        'sales_target',
        'sales_achieved',
        'status'
    ];

    protected $casts = [
        'sales_target' => 'decimal:2',
        'sales_achieved' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Calculate completion percentage
    public function getVisitCompletionPercentageAttribute()
    {
        if ($this->visit_target == 0)
            return 0;
        return round(($this->visits_completed / $this->visit_target) * 100, 2);
    }

    public function getSalesCompletionPercentageAttribute()
    {
        if ($this->sales_target == 0)
            return 0;
        return round(($this->sales_achieved / $this->sales_target) * 100, 2);
    }

    // Update status based on achievement
    public function updateStatus()
    {
        $now = now();
        $targetMonth = \Carbon\Carbon::create($this->year, $this->month, 1);

        if ($now->lt($targetMonth)) {
            $this->status = 'pending';
        } elseif ($now->month == $this->month && $now->year == $this->year) {
            $this->status = 'in_progress';
        } else {
            // Month has passed
            if ($this->visits_completed >= $this->visit_target && $this->sales_achieved >= $this->sales_target) {
                $this->status = 'achieved';
            } else {
                $this->status = 'missed';
            }
        }

        $this->save();
    }
}
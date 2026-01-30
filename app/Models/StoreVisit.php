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
        'visit_summary'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'visit_date' => 'date',
    ];

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


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeStoreAssignment extends Model
{
    protected $fillable = [
        'employee_id',
        'store_id',
        'assigned_date',
        'removed_date',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'assigned_date' => 'date',
        'removed_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

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

    public function scopeMatchingEmployeeState($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId)
            ->active()
            ->whereHas('store', function ($q) use ($employeeId) {
                $q->where('state_id', function ($subQuery) use ($employeeId) {
                    $subQuery->select('state_id')
                        ->from('employees')
                        ->where('id', $employeeId)
                        ->limit(1);
                });
            });
    }

    public function scopeWhereStoreMatchesEmployeeState($query)
    {
        return $query->whereHas('store', function ($q) {
            $q->whereColumn(
                'stores.state_id',
                'employee_store_assignments.employee_id',
                function ($sub) {
                    $sub->select('state_id')
                        ->from('employees')
                        ->whereColumn('employees.id', 'employee_store_assignments.employee_id');
                }
            );
        });
    }
}

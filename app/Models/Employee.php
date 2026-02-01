<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'is_active',
        'user_id',
        'company_id',
        'branch_id',
        'dept_id',
        'zone_id',  // ADDED
        'state_id',
        'city_id',
        'area_id',
        'pin_code',
        'country',
        'name',
        'address',
        'contact_number_1',
        'contact_number_2',
        'email_1',
        'email_2',
        'aadhar_number',
        'aadhar_image',
        'employee_image',
        'dob',
        'doj',
        'designation',
        'reporting_to'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'dob' => 'date',
        'doj' => 'date',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'dept_id');
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'reporting_to');
    }

    public function team()
    {
        return $this->hasMany(Employee::class, 'reporting_to');
    }

    public function storeAssignments()
    {
        return $this->hasMany(EmployeeStoreAssignment::class);
    }

    public function activeStoreAssignments()
    {
        return $this->hasMany(EmployeeStoreAssignment::class)->where('is_active', true);
    }

    public function assignedStores()
    {
        return $this->belongsToMany(Store::class, 'employee_store_assignments')
            ->wherePivot('is_active', true)
            ->withPivot('assigned_date', 'is_active')
            ->withTimestamps();
    }

    public function visits()
    {
        return $this->hasMany(StoreVisit::class);
    }

    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function targets()
    {
        return $this->hasMany(EmployeeTarget::class);
    }

    public function currentMonthTarget()
    {
        return $this->hasOne(EmployeeTarget::class)
            ->where('month', now()->month)
            ->where('year', now()->year);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
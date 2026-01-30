<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'address',
        'state_id',
        'city_id',
        'area_id',
        'pin_code',
        'country',
        'contact_number_1',
        'contact_number_2',
        'email',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];


    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function employees()
    {
        return $this->hasMany(Employee::class);
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

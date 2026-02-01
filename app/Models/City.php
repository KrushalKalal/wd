<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = ['state_id', 'name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    // Access zone through state
    public function zone()
    {
        return $this->hasOneThrough(
            Zone::class,
            State::class,
            'id',
            'id',
            'state_id',
            'zone_id'
        );
    }

    public function areas()
    {
        return $this->hasMany(Area::class);
    }

    public function companies()
    {
        return $this->hasMany(Company::class);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
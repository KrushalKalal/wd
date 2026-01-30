<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = [
        'name',
        'address',
        'state_id',
        'city_id',
        'area_id',
        'pin_code',
        'latitude',
        'longitude',
        'category_one_id',
        'category_two_id',
        'category_three_id',
        'contact_number_1',
        'contact_number_2',
        'email',
        'billing_details',
        'shipping_details',
        'manual_stock_entry',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'manual_stock_entry' => 'boolean',
        'billing_details' => 'array',
        'shipping_details' => 'array',
    ];

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

    public function storeProducts()
    {
        return $this->hasMany(StoreProduct::class);
    }
    public function visits()
    {
        return $this->hasMany(StoreVisit::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function categoryOne()
    {
        return $this->belongsTo(CategoryOne::class, 'category_one_id');
    }

    public function categoryTwo()
    {
        return $this->belongsTo(CategoryTwo::class, 'category_two_id');
    }

    public function categoryThree()
    {
        return $this->belongsTo(CategoryThree::class, 'category_three_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

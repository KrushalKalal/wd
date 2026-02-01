<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $fillable = [
        'offer_type',
        'offer_title',
        'description',
        'offer_percentage',
        'p_category_id',
        'category_one_id',
        'category_two_id',
        'category_three_id',
        'store_ids',
        'min_sales_amount',
        'max_sales_amount',
        'state_id',
        'city_id',
        'area_id',
        'start_date',
        'end_date',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'offer_percentage' => 'decimal:2',
        'min_sales_amount' => 'decimal:2',
        'max_sales_amount' => 'decimal:2',
        'store_ids' => 'array',
    ];

    // Product Category relationship
    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'p_category_id');
    }

    // Store Category relationships
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

    // Location relationships
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

    // Get stores based on store_ids
    public function stores()
    {
        if (!$this->store_ids) {
            return collect([]);
        }
        return Store::whereIn('id', $this->store_ids)->get();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
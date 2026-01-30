<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $fillable = [
        'offer_type',
        'category_one_id',
        'category_two_id',
        'category_three_id',
        'min_quantity',
        'max_quantity',
        'offer_title',
        'description',
        'start_date',
        'end_date',
        'is_active'
    ];


    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

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

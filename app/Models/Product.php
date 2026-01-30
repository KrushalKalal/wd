<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'category_one_id',
        'category_two_id',
        'category_three_id',
        'p_category_id',
        'mrp',
        'edo',
        'total_stock',
        'is_active',
        'catalogue_pdf',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'edo' => 'date',
    ];

    public function categoryOne()
    {
        return $this->belongsTo(CategoryOne::class);
    }
    public function categoryTwo()
    {
        return $this->belongsTo(CategoryTwo::class);
    }
    public function categoryThree()
    {
        return $this->belongsTo(CategoryThree::class);
    }
    public function pCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'p_category_id');
    }

    public function storeProducts()
    {
        return $this->hasMany(StoreProduct::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

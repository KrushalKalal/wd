<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'p_category_id',
        'mrp',  
        'edd',
        'total_stock',
        'is_active',
        'catalogue_pdf',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'edd' => 'decimal:2',
        'mrp' => 'decimal:2',
        'price' => 'decimal:2',
    ];

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
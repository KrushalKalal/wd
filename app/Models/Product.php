<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'p_category_id',
        'mrp',
        'edd',
        'pack_size',
        'volume',
        'state_id',
        'image',
        'total_stock',
        'is_active',
        //'catalogue_pdf',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'edd' => 'decimal:2',
        'mrp' => 'decimal:2',
        'pack_size' => 'integer',
        'volume' => 'integer',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return null;
    }

    public function pCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'p_category_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class);
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
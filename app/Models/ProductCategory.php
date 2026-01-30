<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $fillable = ['name', 'is_active'];
    protected $casts = [
        'is_active' => 'boolean',
    ];


    public function products()
    {
        return $this->hasMany(Product::class, 'p_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

}

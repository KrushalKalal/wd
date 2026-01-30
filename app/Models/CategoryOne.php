<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryOne extends Model
{
    protected $table = 'category_one';
    protected $fillable = ['name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];


    public function products()
    {
        return $this->hasMany(Product::class, 'category_one_id');
    }

    public function offers()
    {
        return $this->hasMany(Offer::class, 'category_one_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

}

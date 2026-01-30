<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryThree extends Model
{
    protected $table = 'category_three';
    protected $fillable = ['name', 'category_two_id', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];


    public function products()
    {
        return $this->hasMany(Product::class, 'category_three_id');
    }

    public function offers()
    {
        return $this->hasMany(Offer::class, 'category_three_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

}

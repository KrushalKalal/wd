<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreProduct extends Model
{
    protected $fillable = [
        'store_id',
        'product_id',
        'current_stock',
        'pending_stock',
        'return_stock'
    ];

    protected $casts = [
        'current_stock' => 'integer',
        'pending_stock' => 'integer',
        'return_stock' => 'integer',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Get available stock (current - pending returns)
    public function getAvailableStockAttribute()
    {
        return $this->current_stock - $this->return_stock;
    }

    // Get total pending (additions + returns)
    public function getTotalPendingAttribute()
    {
        return $this->pending_stock + $this->return_stock;
    }
}

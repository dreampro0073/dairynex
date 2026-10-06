<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'client_id',
        'category_id',
        'name',
        'sku',
        'unit_type',
        'base_rate',
        'status',
    ];

    protected $casts = [
        'base_rate' => 'decimal:2',
        'status' => 'integer',
        'unit_type' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }
}
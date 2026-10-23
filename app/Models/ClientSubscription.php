<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientSubscription extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'status' => 'integer',
        'monthly_price' => 'decimal:2',
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function scopeCurrent($query)
    {
        return $query->where('status', 1)->whereDate('start_date', '<=', today())
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', today()));
    }
}

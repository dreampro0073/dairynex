<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'client_id',
        'customer_id',
        'subscription_no',
        'billing_cycle_id',
        'start_date',
        'end_date',
        'status',
        'auto_generate_deliveries',
        'notes',
    ];

    public function pauses()
    {
        return $this->hasMany(SubscriptionPause::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SubscriptionItem::class);
    }
}
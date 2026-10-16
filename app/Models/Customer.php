<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'client_id',
        'customer_code',
        'name',
        'mobile',
        'email',
        'date_of_birth',
        'gender',
        'opening_balance',
        'credit_limit',
        'payment_mode_id',
        'customer_type',
        'status',
        'supply_preference',
        'notes',
        'delivery_pin_required',
    ];

    protected $casts = ['client_id' => 'integer', 'status' => 'integer', 'delivery_pin_required' => 'boolean'];

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function defaultAddress()
    {
        return $this->hasOne(CustomerAddress::class)
            ->where('is_default', 1);
    }

    public function routes()
    {
        return $this->belongsToMany(
            DeliveryRoute::class,
            'delivery_route_customers',
            'customer_id',
            'route_id'
        )->withPivot('sequence_no');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
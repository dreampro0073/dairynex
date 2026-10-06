<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryRoute extends Model
{
    protected $fillable = [
        'client_id',
        'delivery_boy_id',
        'name',
        'area_locality',
        'expected_stops',
        'route_date',
        'status',
    ];

    protected $casts = [
        'route_date' => 'date',
        'status' => 'integer',
        'expected_stops' => 'integer',
    ];

    public function deliveryBoy()
    {
        return $this->belongsTo(
            User::class,
            'delivery_boy_id'
        );
    }

    public function customers()
    {
        return $this->belongsToMany(
            Customer::class,
            'delivery_route_customers',
            'route_id',
            'customer_id'
        )->withPivot('sequence_no');
    }
}
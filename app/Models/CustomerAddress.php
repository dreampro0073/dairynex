<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    protected $fillable = [
        'client_id',
        'customer_id',
        'address_type',
        'address_line1',
        'address_line2',
        'landmark',
        'city',
        'state',
        'pincode',
        'latitude',
        'longitude',
        'is_default',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
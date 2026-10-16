<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Delivery extends Model
{
    protected $fillable = [
        'client_id', 'customer_id', 'subscription_id', 'route_id', 'delivery_boy_id',
        'delivery_date', 'delivery_slot_id', 'source_type', 'status_id', 'total_amount',
    ];

    protected $hidden = ['delivery_pin_hash'];

    protected $casts = ['status_id' => 'integer', 'pin_attempts' => 'integer', 'delivery_pin_verified_at' => 'datetime', 'pin_required' => 'boolean', 'delivery_date' => 'date', 'total_amount' => 'decimal:2'];

    protected static function booted(): void
    {
        static::updating(function (Delivery $delivery) {
            if ($delivery->isDirty('pin_required')) {
                throw ValidationException::withMessages(['pin_required' => 'A delivery policy snapshot cannot be changed.']);
            }
        });
        static::creating(function (Delivery $delivery) {
            $customer = Customer::where('client_id', $delivery->client_id)->find($delivery->customer_id);
            if (!$customer) {
                throw ValidationException::withMessages(['customer_id' => 'Customer does not belong to this dairy.']);
            }
            // A caller cannot supply a weaker policy. Later customer changes never
            // update this stored snapshot or any verification history.
            $delivery->pin_required = $customer->delivery_pin_required;
        });
    }
}

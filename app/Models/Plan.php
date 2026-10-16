<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['monthly_price' => 'decimal:2', 'status' => 'integer', 'max_customers' => 'integer', 'max_users' => 'integer'];

    public function subscriptions()
    {
        return $this->hasMany(ClientSubscription::class);
    }
}

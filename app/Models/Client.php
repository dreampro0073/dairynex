<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public function admins()
    {
        return $this->hasMany(User::class)->where('user_type_id', Role::ClientAdmin->value);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function deliveryBoys()
    {
        return $this->hasMany(User::class)->where('user_type_id', Role::DeliveryBoy->value);
    }

    public function currentSubscription()
    {
        return $this->hasOne(ClientSubscription::class)->ofMany(['id' => 'max'], fn ($q) => $q->current());
    }
}

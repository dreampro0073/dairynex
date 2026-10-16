<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'client_id' => 'integer',
            'user_type_id' => 'integer',
            'status' => 'integer',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'temporary_password_expires_at' => 'datetime',
        ];
    }

    public function role(): ?\App\Enums\Role
    {
        return \App\Enums\Role::tryFrom((int) $this->user_type_id);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function canSignIn(): bool
    {
        if ((int) $this->status !== 1 || $this->role() === null || empty($this->password)) {
            return false;
        }

        if ($this->must_change_password && $this->temporary_password_expires_at?->isPast()) {
            return false;
        }

        return $this->role() === \App\Enums\Role::SuperAdmin
            || ($this->client_id && (int) $this->client?->status === 1);
    }

}

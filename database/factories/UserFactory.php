<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'user_type_id' => Role::Customer->value,
            'name' => fake()->name(),
            'mobile' => fake()->unique()->numerify('9#########'),
            'email' => fake()->unique()->safeEmail(),
            'status' => 1,
            'password' => Hash::make(Str::random(32)),
            'remember_token' => Str::random(60),
        ];
    }

    public function unverified(): static
    {
        // The reference schema has no email verification column.
        return $this;
    }
}

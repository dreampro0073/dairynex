<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Customer;
use App\Models\Delivery;
use Tests\BusinessTestCase;

class ApiV1Test extends BusinessTestCase
{
    public function test_sanctum_token_api_returns_json_and_enforces_tenant_delivery_scope(): void
    {
        $admin = $this->account(Role::ClientAdmin, 1, ['mobile' => '9000000001']);
        $customer = Customer::create(['client_id' => 2, 'name' => 'Other dairy customer', 'delivery_pin_required' => false]);
        $delivery = Delivery::create([
            'client_id' => 2,
            'customer_id' => $customer->id,
            'delivery_date' => today(),
            'status_id' => 1,
        ]);

        $tokenResponse = $this->postJson('/api/v1/auth/token', [
            'login' => '9000000001',
            'client_id' => 1,
            'password' => 'test-password-123',
            'device_name' => 'feature-test',
        ])->assertCreated()->assertJsonPath('user.role', 'client_admin');

        $token = $tokenResponse->json('token');
        $this->withToken($token)->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.client_id', 1);
        $this->withToken($token)->getJson('/api/v1/deliveries/'.$delivery->id)->assertNotFound();
    }

    public function test_api_rejects_unauthenticated_requests_as_json(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }
}

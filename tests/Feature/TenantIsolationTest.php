<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Customer;
use App\Models\DeliveryRoute;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Tests\BusinessTestCase;

class TenantIsolationTest extends BusinessTestCase
{
    public function test_guests_and_non_admin_roles_cannot_use_admin_endpoints(): void
    {
        foreach (['/customers', '/products', '/categories', '/routes', '/delivery-boys'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
        foreach ([Role::SuperAdmin, Role::DeliveryBoy, Role::Customer] as $role) {
            $this->flushSession();
            $this->actingAs($this->account($role));
            foreach (['/customers', '/products', '/categories', '/routes', '/delivery-boys'] as $url) {
                $this->get($url)->assertForbidden();
                $this->post($url, [])->assertForbidden();
            }
        }
    }

    public function test_all_admin_resource_updates_and_deletes_reject_other_tenants(): void
    {
        $otherBoy = $this->account(Role::DeliveryBoy, 2);
        $category = ProductCategory::create(['client_id' => 2, 'name' => 'Other category', 'status' => 1]);
        $product = Product::create(['client_id' => 2, 'name' => 'Other product', 'unit_type' => 1, 'base_rate' => 50, 'status' => 1]);
        $customer = Customer::create(['client_id' => 2, 'name' => 'Other customer']);
        $route = DeliveryRoute::create(['client_id' => 2, 'name' => 'Other route', 'delivery_boy_id' => $otherBoy->id]);
        $this->actingAs($this->account());
        foreach (["/categories/{$category->id}", "/products/{$product->id}", "/customers/{$customer->id}", "/routes/{$route->id}", "/delivery-boys/{$otherBoy->id}"] as $url) {
            $this->put($url, [])->assertForbidden();
            $this->delete($url)->assertForbidden();
        }
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'client_id' => 2]);
    }

    public function test_list_and_dropdown_queries_do_not_include_other_tenant_records(): void
    {
        ProductCategory::create(['client_id' => 1, 'name' => 'Visible category', 'status' => 1]);
        ProductCategory::create(['client_id' => 2, 'name' => 'Private category', 'status' => 1]);
        $visible = $this->account(Role::DeliveryBoy, 1);
        $this->account(Role::DeliveryBoy, 2);
        $this->account(3, 1);
        $this->actingAs($this->account());
        $this->get('/categories')->assertInertia(fn (AssertableInertia $page) => $page->has('categories', 1)->where('categories.0.name', 'Visible category'));
        $this->get('/routes/create')->assertInertia(fn (AssertableInertia $page) => $page->has('deliveryBoys', 1)->where('deliveryBoys.0.id', $visible->id));
    }

    public function test_frontend_cannot_choose_the_tenant_for_a_new_record(): void
    {
        $this->actingAs($this->account(clientId: 2));
        $this->post('/categories', ['name' => 'Owned by two', 'status' => 1, 'client_id' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_categories', ['name' => 'Owned by two', 'client_id' => 2]);
        $this->assertDatabaseMissing('product_categories', ['name' => 'Owned by two', 'client_id' => 1]);
    }

    public function test_related_records_must_be_active_and_owned_by_the_admins_dairy(): void
    {
        $otherBoy = $this->account(Role::DeliveryBoy, 2);
        $manager = $this->account(3);
        $inactive = $this->account(Role::DeliveryBoy, attributes: ['status' => 0]);
        $category = ProductCategory::create(['client_id' => 2, 'name' => 'Private category']);
        $this->actingAs($this->account());
        foreach ([$otherBoy, $manager, $inactive] as $boy) {
            $this->post('/routes', ['name' => 'Test route', 'delivery_boy_id' => $boy->id, 'status' => 1])->assertSessionHasErrors('delivery_boy_id');
        }
        $this->post('/products', ['name' => 'Milk', 'category_id' => $category->id, 'unit_type' => 1, 'base_rate' => 50, 'status' => 1])->assertSessionHasErrors('category_id');
    }

    public function test_staff_creation_hashes_password_and_cannot_claim_another_tenants_route(): void
    {
        $route = DeliveryRoute::create(['client_id' => 2, 'name' => 'Private route', 'delivery_boy_id' => 0]);
        $this->actingAs($this->account());
        $data = ['name' => 'New driver', 'mobile' => '9876543210', 'status' => 1, 'password' => 'driver-password-123', 'password_confirmation' => 'driver-password-123'];
        $this->post('/delivery-boys', [...$data, 'route_id' => $route->id])->assertSessionHasErrors('route_id');
        $this->assertDatabaseMissing('users', ['mobile' => $data['mobile']]);
        $this->post('/delivery-boys', $data)->assertRedirect('/delivery-boys');
        $driver = User::where('mobile', $data['mobile'])->firstOrFail();
        $this->assertSame(Role::DeliveryBoy, $driver->role());
        $this->assertSame(1, $driver->client_id);
        $this->assertTrue(Hash::check($data['password'], $driver->password));
        $this->get('/delivery-boys')->assertDontSee($driver->password);
    }

    public function test_route_conflicts_rollback_staff_creation_and_preserve_assignment(): void
    {
        $existing = $this->account(Role::DeliveryBoy);
        $route = DeliveryRoute::create(['client_id' => 1, 'name' => 'Busy route', 'delivery_boy_id' => $existing->id]);
        $this->actingAs($this->account());
        $this->post('/delivery-boys', ['name' => 'Conflict', 'mobile' => '9876543210', 'route_id' => $route->id, 'status' => 1, 'password' => 'driver-password-123', 'password_confirmation' => 'driver-password-123'])->assertSessionHasErrors('route_id');
        $this->assertDatabaseMissing('users', ['mobile' => '9876543210']);
        $this->assertSame($existing->id, (int) $route->fresh()->delivery_boy_id);
    }

    public function test_role_portals_enforce_ownership_and_do_not_expose_customer_data_by_mobile(): void
    {
        $boy = $this->account(Role::DeliveryBoy);
        $other = $this->account(Role::DeliveryBoy);
        $customer = Customer::create(['client_id' => 1, 'name' => 'Private customer']);
        foreach ([$boy, $other] as $assigned) {
            DB::table('deliveries')->insert(['client_id' => 1, 'customer_id' => $customer->id, 'delivery_boy_id' => $assigned->id, 'delivery_date' => today(), 'route_id' => 1, 'status_id' => 1, 'total_amount' => 50]);
        }
        $this->actingAs($boy)->get('/delivery')->assertInertia(fn (AssertableInertia $page) => $page->has('deliveries.data', 1));
        $this->get('/platform')->assertForbidden();
        $this->get('/customer')->assertForbidden();
        $this->flushSession();
        $this->actingAs($this->account(Role::Customer))->get('/customer')->assertOk()->assertDontSee('Private customer');
    }
}

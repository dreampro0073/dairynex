<?php

namespace Tests\Feature;

use App\Http\Controllers\DeliveryBoyController;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class InertiaFoundationTest extends TestCase
{
    public function test_delivery_boy_routes_resolve_to_existing_controller_methods(): void
    {
        foreach (['index', 'create', 'store', 'edit', 'update', 'destroy'] as $method) {
            $this->assertTrue(method_exists(DeliveryBoyController::class, $method));
            $this->assertTrue(Route::has('delivery-boys.'.$method));
        }
    }

    public function test_web_requests_share_flash_and_validation_errors_through_inertia(): void
    {
        Route::middleware('web')->get('/_test/inertia', fn () => Inertia::render('DeliveryBoys/Index'));

        $this->withSession(['success' => 'Delivery boy added successfully.'])
            ->get('/_test/inertia')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('DeliveryBoys/Index')
                ->where('flash.success', 'Delivery boy added successfully.')
                ->where('flash.error', null)
                ->has('errors'));
    }

    public function test_existing_admin_routes_use_the_inertia_middleware(): void
    {
        $this->get('/')->assertRedirect('/login');

        $route = Route::getRoutes()->match(Request::create('/dashboard'));
        $this->assertContains(HandleInertiaRequests::class, app('router')->gatherRouteMiddleware($route));
    }
}

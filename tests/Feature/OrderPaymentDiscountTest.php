<?php

use App\Enum\UserRoleEnum;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Inventory;
use App\Models\Menu;
use App\Models\MenuContent;
use App\Models\MenuOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function paymentOrderFixture(int $stock = 20): array
{
    $category = Category::create(['name' => 'drinks']);
    $menu = Menu::create(['category_id' => $category->id, 'name' => 'milk tea']);
    $inventory = Inventory::create(['name' => 'tea', 'type' => 5, 'stock_in_pcs' => $stock]);
    $option = MenuOption::create([
        'menu_id' => $menu->id,
        'name' => 'large',
        'type' => 'size',
        'price' => 100,
    ]);
    MenuContent::create([
        'menu_option_id' => $option->id,
        'inventory_id' => $inventory->id,
        'name' => 'tea',
        'stock_to_deduct_in_pcs' => 2,
    ]);

    return [$option, $inventory];
}

function checkoutPayload(MenuOption $option): array
{
    return [
        'intent' => 'check-out',
        'payment_method' => 'cash',
        'items' => [['product_option_id' => $option->id, 'quantity' => 1]],
    ];
}

test('checkout records the order payment and applied discount.', function () {
    [$option, $inventory] = paymentOrderFixture();
    $cashier = User::factory()->create(['role' => UserRoleEnum::cashier]);
    Coupon::create([
        'code' => 'SAVE10',
        'type' => 'percentage',
        'value' => 10,
        'minimum_purchase' => 50,
    ]);

    $response = \Pest\Laravel\actingAs($cashier)->postJson('api/v1/orders/checkout', [
        ...checkoutPayload($option),
        'discount_code' => 'save10',
        'payment_method' => 'gcash',
        'gcash_reference_number' => 'GC-123',
        'gcash_customer_number' => '09171234567',
    ]);

    $response->assertCreated()->assertJsonPath('order.total', '90.00');
    \Pest\Laravel\assertDatabaseHas('orders', [
        'subtotal' => 100,
        'discount_amount' => 10,
        'total' => 90,
        'coupon_code' => 'SAVE10',
    ]);
    \Pest\Laravel\assertDatabaseHas('payment_records', [
        'payment_method' => 'gcash',
        'gcash_customer_number' => '09171234567',
        'gcash_reference_number' => 'GC-123',
        'amount' => 90,
    ]);
    \Pest\Laravel\assertDatabaseHas('inventories', ['id' => $inventory->id, 'stock_in_pcs' => 18]);
});

test('gcash checkout requires a reference number but customer number is optional.', function () {
    [$option] = paymentOrderFixture();
    $cashier = User::factory()->create(['role' => UserRoleEnum::cashier]);

    \Pest\Laravel\actingAs($cashier)->postJson('api/v1/orders/checkout', [
        ...checkoutPayload($option),
        'payment_method' => 'gcash',
    ])->assertUnprocessable()->assertJsonValidationErrors('gcash_reference_number');
});

test('an invalid discount code stops checkout before inventory changes.', function () {
    [$option, $inventory] = paymentOrderFixture();
    $cashier = User::factory()->create(['role' => UserRoleEnum::cashier]);

    \Pest\Laravel\actingAs($cashier)->postJson('api/v1/orders/checkout', [
        ...checkoutPayload($option),
        'discount_code' => 'NOT-VALID',
    ])->assertUnprocessable()->assertJsonPath('message', 'The discount code is invalid.');

    \Pest\Laravel\assertDatabaseCount('orders', 0);
    \Pest\Laravel\assertDatabaseHas('inventories', ['id' => $inventory->id, 'stock_in_pcs' => 20]);
});

test('admins can void an order and stock is restored while payment remains auditable.', function () {
    [$option, $inventory] = paymentOrderFixture();
    $admin = User::factory()->create(['role' => UserRoleEnum::admin]);
    $cashier = User::factory()->create(['role' => UserRoleEnum::cashier]);

    $checkout = \Pest\Laravel\actingAs($cashier)->postJson('api/v1/orders/checkout', checkoutPayload($option))
        ->assertCreated();
    $orderId = $checkout->json('order.id');

    \Pest\Laravel\actingAs($cashier)->postJson("api/v1/orders/$orderId/void", ['reason' => 'customer_request'])
        ->assertForbidden();

    \Pest\Laravel\actingAs($admin)->postJson("api/v1/orders/$orderId/void", ['reason' => 'customer_request'])
        ->assertOk();

    \Pest\Laravel\assertDatabaseHas('orders', ['id' => $orderId, 'status' => 'voided', 'void_reason' => 'customer_request']);
    \Pest\Laravel\assertDatabaseHas('payment_records', ['order_id' => $orderId]);
    \Pest\Laravel\assertDatabaseHas('inventories', ['id' => $inventory->id, 'stock_in_pcs' => 20]);
});

test('order lists and sales reports support status and date filtering.', function () {
    [$option] = paymentOrderFixture();
    $cashier = User::factory()->create(['role' => UserRoleEnum::cashier]);

    $checkout = \Pest\Laravel\actingAs($cashier)->postJson('api/v1/orders/checkout', checkoutPayload($option))
        ->assertCreated();
    $orderId = $checkout->json('order.id');

    \Pest\Laravel\actingAs($cashier)->putJson("api/v1/orders/$orderId/status", ['status' => 'ready'])
        ->assertOk();

    \Pest\Laravel\actingAs($cashier)->getJson('api/v1/orders?status=ready')
        ->assertOk()
        ->assertJsonPath('0.status', 'ready');
    \Pest\Laravel\actingAs($cashier)->getJson('api/v1/orders/sales-report')
        ->assertOk()
        ->assertJsonPath('sales.orders', 1)
        ->assertJsonPath('revenue.total', 100);
});

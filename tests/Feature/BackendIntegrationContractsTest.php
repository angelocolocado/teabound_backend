<?php

use App\Enum\UserRoleEnum;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\LoginActivity;
use App\Models\Menu;
use App\Models\MenuContent;
use App\Models\MenuOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function integrationOrderFixture(): MenuOption
{
    $category = Category::create(['name' => 'drinks']);
    $menu = Menu::create(['category_id' => $category->id, 'name' => 'milk tea']);
    $inventory = Inventory::create(['name' => 'tea', 'type' => 5, 'stock_in_pcs' => 20]);
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

    return $option;
}

function integrationCheckoutPayload(MenuOption $option): array
{
    return [
        'intent' => 'check-out',
        'payment_method' => 'cash',
        'items' => [['product_option_id' => $option->id, 'quantity' => 1]],
    ];
}

test('cash checkout stores tendered cash and returns change.', function () {
    $option = integrationOrderFixture();
    $cashier = User::factory()->create(['role' => UserRoleEnum::cashier]);

    \Pest\Laravel\actingAs($cashier)->postJson('api/v1/orders/checkout', [
        ...integrationCheckoutPayload($option),
        'cash_received' => 125,
    ])->assertCreated()
        ->assertJsonPath('order.payment_record.cash_received', '125.00')
        ->assertJsonPath('order.payment_record.change_amount', '25.00');
});

test('inventory low-stock endpoint returns threshold fields.', function () {
    $cashier = User::factory()->create(['role' => UserRoleEnum::cashier]);
    Inventory::create([
        'name' => 'Tea Leaves',
        'type' => 5,
        'stock_in_pcs' => 4,
        'low_stock_threshold' => 5,
        'display_unit' => 'bags',
    ]);

    \Pest\Laravel\actingAs($cashier)->getJson('api/v1/inventories/low-stock')
        ->assertOk()
        ->assertJsonPath('0.stock_in_pcs', 4)
        ->assertJsonPath('0.low_stock_threshold', 5)
        ->assertJsonPath('0.display_unit', 'bags');
});

test('activity endpoints expose login history and audit contracts.', function () {
    $admin = User::factory()->create(['role' => UserRoleEnum::admin]);
    LoginActivity::create([
        'user_id' => $admin->id,
        'name' => $admin->name,
        'role' => 'admin',
        'activity_type' => 'login',
        'occurred_at' => now(),
    ]);
    AuditLog::create([
        'user_id' => $admin->id,
        'action' => 'order.checkout',
        'description' => 'Order checked out.',
        'status' => 'success',
    ]);

    \Pest\Laravel\actingAs($admin)->getJson('api/v1/login-history')
        ->assertOk()
        ->assertJsonPath('0.activity_type', 'login')
        ->assertJsonPath('0.role', 'admin');
    \Pest\Laravel\actingAs($admin)->getJson('api/v1/audit-activity')
        ->assertOk()
        ->assertJsonPath('0.action', 'order.checkout')
        ->assertJsonPath('0.status', 'success');
});

test('admins can upload a menu image and receive its public URL.', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => UserRoleEnum::admin]);
    $category = Category::create(['name' => 'drinks']);
    $menu = Menu::create(['category_id' => $category->id, 'name' => 'milk tea']);

    \Pest\Laravel\actingAs($admin)->post('api/v1/menus/'.$menu->id.'/image', [
        'image' => UploadedFile::fake()->image('menu.jpg'),
    ])->assertOk()
        ->assertJsonPath('id', $menu->id);

    Storage::disk('public')->assertExists($menu->refresh()->image_path);
});

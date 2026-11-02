<?php

use App\Enum\MenuStatusEnum;
use App\Enum\UserRoleEnum;
use App\Models\Category;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function menuPayload(Category $category, string $name = 'milk tea'): array
{
    return [
        'category_id' => $category->id,
        'name' => $name,
        'notes' => 'House blend',
        'price' => 120.50,
    ];
}

test('cashiers can list menus with their categories.', function () {
    $user = User::factory()->create(['role' => UserRoleEnum::cashier]);
    $category = Category::create(['name' => 'milk tea']);
    Menu::create(menuPayload($category));

    \Pest\Laravel\actingAs($user)->getJson('api/v1/menus')
        ->assertOk()
        ->assertJsonPath('0.name', 'Milk Tea')
        ->assertJsonPath('0.category', 'Milk Tea')
        ->assertJsonPath('0.status', 'inactive');
});

test('cashiers can view a menu.', function () {
    $user = User::factory()->create(['role' => UserRoleEnum::cashier]);
    $category = Category::create(['name' => 'milk tea']);
    $menu = Menu::create(menuPayload($category));

    \Pest\Laravel\actingAs($user)->getJson("api/v1/menus/$menu->id")
        ->assertOk()
        ->assertJsonPath('id', $menu->id)
        ->assertJsonPath('name', 'Milk Tea')
        ->assertJsonPath('category', 'Milk Tea');
});

test('only admins can create menus.', function () {
    $admin = User::factory()->create(['role' => UserRoleEnum::admin]);
    $cashier = User::factory()->create(['role' => UserRoleEnum::cashier]);
    $category = Category::create(['name' => 'milk tea']);
    $payload = menuPayload($category, '  MILK TEA  ');

    \Pest\Laravel\actingAs($admin)->postJson('api/v1/menus', $payload)
        ->assertCreated()
        ->assertJsonPath('name', 'Milk Tea')
        ->assertJsonPath('status', 'inactive');

    \Pest\Laravel\actingAs($cashier)->postJson('api/v1/menus', $payload)
        ->assertForbidden();

    $this->postJson('api/v1/menus', $payload)->assertForbidden();
});

test('menu creation rejects an unknown category.', function () {
    $admin = User::factory()->create(['role' => UserRoleEnum::admin]);

    \Pest\Laravel\actingAs($admin)->postJson('api/v1/menus', [
        'category_id' => 999,
        'name' => 'Milk Tea',
        'price' => 120,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('category_id');
});

test('only admins can update menu details.', function () {
    $admin = User::factory()->create(['role' => UserRoleEnum::admin]);
    $cashier = User::factory()->create(['role' => UserRoleEnum::cashier]);
    $category = Category::create(['name' => 'milk tea']);
    $menu = Menu::create(menuPayload($category));
    $payload = menuPayload($category, 'green tea');

    \Pest\Laravel\actingAs($cashier)->putJson("api/v1/menus/$menu->id", $payload)
        ->assertForbidden();

    \Pest\Laravel\actingAs($admin)->putJson("api/v1/menus/$menu->id", $payload)
        ->assertNoContent();

    \Pest\Laravel\assertDatabaseHas('menus', ['id' => $menu->id, 'name' => 'green tea']);
});

test('admins can change menu status.', function (string $endpoint, MenuStatusEnum $status) {
    $admin = User::factory()->create(['role' => UserRoleEnum::admin]);
    $category = Category::create(['name' => 'milk tea']);
    $menu = Menu::create(menuPayload($category));

    \Pest\Laravel\actingAs($admin)->putJson("api/v1/menus/$menu->id/$endpoint")
        ->assertNoContent();

    \Pest\Laravel\assertDatabaseHas('menus', [
        'id' => $menu->id,
        'status' => $status->value,
    ]);
})->with([
    'publishes' => ['publish', MenuStatusEnum::active],
    'deactivates' => ['deactivate', MenuStatusEnum::inactive],
    'marks pulled out' => ['flag-pulled-out', MenuStatusEnum::pulled_out],
    'marks out of stock' => ['mark-out-of-stock', MenuStatusEnum::out_of_stock],
]);

test('only admins can delete menus.', function () {
    $admin = User::factory()->create(['role' => UserRoleEnum::admin]);
    $cashier = User::factory()->create(['role' => UserRoleEnum::cashier]);
    $category = Category::create(['name' => 'milk tea']);
    $menu = Menu::create(menuPayload($category));

    \Pest\Laravel\actingAs($cashier)->deleteJson("api/v1/menus/$menu->id")
        ->assertForbidden();

    \Pest\Laravel\actingAs($admin)->deleteJson("api/v1/menus/$menu->id")
        ->assertNoContent();

    \Pest\Laravel\assertDatabaseMissing('menus', ['id' => $menu->id]);
});

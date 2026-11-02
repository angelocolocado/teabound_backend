<?php

use App\Enum\UserRoleEnum;
use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function menuOptionPayload(string $name = 'large'): array
{
    return [
        'name' => $name,
        'type' => 'size',
        'price' => 15,
        'is_active' => true,
        'sort_order' => 1,
    ];
}

function menuForOptions(): Menu
{
    $category = Category::create(['name' => 'milk tea']);

    return Menu::create([
        'category_id' => $category->id,
        'name' => 'classic milk tea',
        'price' => 100,
    ]);
}

test('cashiers can list menu options for the cashier interface.', function () {
    $cashier = User::factory()->create(['role' => UserRoleEnum::cashier]);
    $menu = menuForOptions();
    MenuOption::create(['menu_id' => $menu->id, ...menuOptionPayload()]);
    MenuOption::create([
        'menu_id' => $menu->id,
        ...menuOptionPayload('pearls'),
        'type' => 'topping',
        'price' => 10,
        'sort_order' => 2,
    ]);

    \Pest\Laravel\actingAs($cashier)->getJson("api/v1/menus/$menu->id/options")
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.name', 'Large')
        ->assertJsonPath('0.type', 'size')
        ->assertJsonPath('1.name', 'Pearls')
        ->assertJsonPath('1.type', 'topping');
});

test('menu option endpoints require authentication.', function () {
    $menu = menuForOptions();

    $this->getJson("api/v1/menus/$menu->id/options")
        ->assertUnauthorized();
});

test('only admins can create menu options.', function () {
    $admin = User::factory()->create(['role' => UserRoleEnum::admin]);
    $cashier = User::factory()->create(['role' => UserRoleEnum::cashier]);
    $menu = menuForOptions();

    \Pest\Laravel\actingAs($admin)->postJson("api/v1/menus/$menu->id/options", menuOptionPayload())
        ->assertCreated()
        ->assertJsonPath('name', 'Large');

    \Pest\Laravel\actingAs($cashier)->postJson("api/v1/menus/$menu->id/options", menuOptionPayload('extra pearls'))
        ->assertForbidden();

    \Pest\Laravel\assertDatabaseHas('menu_options', [
        'menu_id' => $menu->id,
        'name' => 'large',
        'type' => 'size',
    ]);
});

test('menu option creation rejects unsupported types and negative prices.', function () {
    $admin = User::factory()->create(['role' => UserRoleEnum::admin]);
    $menu = menuForOptions();

    \Pest\Laravel\actingAs($admin)->postJson("api/v1/menus/$menu->id/options", [
        'name' => 'large',
        'type' => 'syrup',
        'price' => -1,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['type', 'price']);
});

test('only admins can update and delete menu options.', function () {
    $admin = User::factory()->create(['role' => UserRoleEnum::admin]);
    $cashier = User::factory()->create(['role' => UserRoleEnum::cashier]);
    $menu = menuForOptions();
    $menuOption = MenuOption::create(['menu_id' => $menu->id, ...menuOptionPayload()]);

    \Pest\Laravel\actingAs($cashier)->putJson("api/v1/menu-options/$menuOption->id", [
        'price' => 20,
    ])->assertForbidden();

    \Pest\Laravel\actingAs($admin)->putJson("api/v1/menu-options/$menuOption->id", [
        'name' => 'extra large',
        'price' => 20,
        'is_active' => false,
    ])->assertNoContent();

    \Pest\Laravel\assertDatabaseHas('menu_options', [
        'id' => $menuOption->id,
        'name' => 'extra large',
        'price' => 20,
        'is_active' => false,
    ]);

    \Pest\Laravel\actingAs($cashier)->deleteJson("api/v1/menu-options/$menuOption->id")
        ->assertForbidden();

    \Pest\Laravel\actingAs($admin)->deleteJson("api/v1/menu-options/$menuOption->id")
        ->assertNoContent();

    \Pest\Laravel\assertDatabaseMissing('menu_options', ['id' => $menuOption->id]);
});

test('menu option updates reject duplicate names within the same type.', function () {
    $admin = User::factory()->create(['role' => UserRoleEnum::admin]);
    $menu = menuForOptions();
    MenuOption::create(['menu_id' => $menu->id, ...menuOptionPayload('large')]);
    $otherOption = MenuOption::create(['menu_id' => $menu->id, ...menuOptionPayload('extra large')]);

    \Pest\Laravel\actingAs($admin)->putJson("api/v1/menu-options/$otherOption->id", [
        'name' => 'large',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('menu responses include configured options.', function () {
    $cashier = User::factory()->create(['role' => UserRoleEnum::cashier]);
    $menu = menuForOptions();
    MenuOption::create(['menu_id' => $menu->id, ...menuOptionPayload()]);

    \Pest\Laravel\actingAs($cashier)->getJson("api/v1/menus/$menu->id")
        ->assertOk()
        ->assertJsonPath('menu_options.0.name', 'Large');
});

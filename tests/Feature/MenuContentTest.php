<?php

use App\Enum\UserRoleEnum;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Menu;
use App\Models\MenuContent;
use App\Models\MenuOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function menuWithOption(string $optionName = 'large'): array
{
    $category = Category::create(['name' => 'milk tea']);
    $menu = Menu::create([
        'category_id' => $category->id,
        'name' => 'classic milk tea',
    ]);
    $option = MenuOption::create([
        'menu_id' => $menu->id,
        'name' => $optionName,
        'type' => 'size',
        'price' => 15,
    ]);

    return [$menu, $option];
}

test('cashiers can list contents belonging to a menu option.', function () {
    $cashier = User::factory()->create(['role' => UserRoleEnum::cashier]);
    [$menu, $option] = menuWithOption();
    $inventory = Inventory::create([
        'name' => 'Tea leaves',
        'type' => 5,
        'stock_in_pcs' => 20,
    ]);
    MenuContent::create([
        'menu_option_id' => $option->id,
        'inventory_id' => $inventory->id,
        'name' => 'tea leaves',
        'stock_to_deduct_in_pcs' => 2,
    ]);

    \Pest\Laravel\actingAs($cashier)
        ->getJson("api/v1/menus/$menu->id/options/$option->id/contents")
        ->assertOk()
        ->assertJsonPath('0.menu_option_id', $option->id)
        ->assertJsonPath('0.inventory.id', $inventory->id)
        ->assertJsonPath('0.stock_to_deduct_in_pcs', 2);
});

test('admins can create contents for a menu option.', function () {
    $admin = User::factory()->create(['role' => UserRoleEnum::admin]);
    [$menu, $option] = menuWithOption();
    $inventory = Inventory::create([
        'name' => 'Tea leaves',
        'type' => 5,
        'stock_in_pcs' => 20,
    ]);

    \Pest\Laravel\actingAs($admin)
        ->postJson("api/v1/menus/$menu->id/options/$option->id/contents", [
            'inventory_id' => $inventory->id,
            'name' => 'tea leaves',
            'stock_to_deduct_in_pcs' => 2,
        ])
        ->assertCreated()
        ->assertJsonPath('menu_option_id', $option->id);

    \Pest\Laravel\assertDatabaseHas('menu_contents', [
        'menu_option_id' => $option->id,
        'inventory_id' => $inventory->id,
    ]);
});

test('nested menu option content routes reject an option from another menu.', function () {
    $cashier = User::factory()->create(['role' => UserRoleEnum::cashier]);
    [$menu] = menuWithOption();
    [, $otherOption] = menuWithOption('small');

    \Pest\Laravel\actingAs($cashier)
        ->getJson("api/v1/menus/$menu->id/options/$otherOption->id/contents")
        ->assertNotFound();
});

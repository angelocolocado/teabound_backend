<?php

use App\Exceptions\InsufficientInventoryException;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Menu;
use App\Models\MenuContent;
use App\Models\MenuOption;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function inventoryOption(string $name, Inventory $inventory, int $stockToDeduct): MenuOption
{
    $category = Category::firstOrCreate(['name' => 'milk tea']);
    $menu = Menu::firstOrCreate([
        'category_id' => $category->id,
        'name' => 'classic milk tea',
    ]);
    $option = MenuOption::create([
        'menu_id' => $menu->id,
        'name' => $name,
        'type' => 'size',
        'price' => 15,
    ]);
    MenuContent::create([
        'menu_option_id' => $option->id,
        'inventory_id' => $inventory->id,
        'name' => $inventory->name,
        'stock_to_deduct_in_pcs' => $stockToDeduct,
    ]);

    return $option;
}

test('deduction uses the contents of the selected option only.', function () {
    $inventory = Inventory::create([
        'name' => 'Tea leaves',
        'type' => 5,
        'stock_in_pcs' => 20,
    ]);
    $large = inventoryOption('large', $inventory, 2);
    $small = inventoryOption('small', $inventory, 1);

    $result = InventoryService::deductInventory([
        ['product_option_id' => $large->id, 'quantity' => 3],
    ]);

    expect($result)->toBe(['deducted' => [$inventory->id => 6]]);
    \Pest\Laravel\assertDatabaseHas('inventories', [
        'id' => $inventory->id,
        'stock_in_pcs' => 14,
    ]);
});

test('deduction aggregates shared inventory requirements across selected options.', function () {
    $inventory = Inventory::create([
        'name' => 'Tea leaves',
        'type' => 5,
        'stock_in_pcs' => 20,
    ]);
    $large = inventoryOption('large', $inventory, 2);
    $topping = inventoryOption('pearls', $inventory, 1);

    InventoryService::deductInventory([
        ['product_option_id' => $large->id, 'quantity' => 2],
        ['product_option_id' => $topping->id, 'quantity' => 3],
    ]);

    \Pest\Laravel\assertDatabaseHas('inventories', [
        'id' => $inventory->id,
        'stock_in_pcs' => 13,
    ]);
});

test('deduction is atomic when any selected option lacks enough stock.', function () {
    $inventory = Inventory::create([
        'name' => 'Tea leaves',
        'type' => 5,
        'stock_in_pcs' => 3,
    ]);
    $option = inventoryOption('large', $inventory, 2);

    expect(fn () => InventoryService::deductInventory([
        ['product_option_id' => $option->id, 'quantity' => 2],
    ]))->toThrow(InsufficientInventoryException::class);

    \Pest\Laravel\assertDatabaseHas('inventories', [
        'id' => $inventory->id,
        'stock_in_pcs' => 3,
    ]);
});

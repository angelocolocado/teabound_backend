<?php

use App\Enum\UserRoleEnum;
use App\Models\Inventory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('Authenticated users can list inventory stocks.', function () {

    $user = User::factory()->create(['role' => UserRoleEnum::cashier]);

    Inventory::create([
        'name' => 'Tea Leaves',
        'type' => 5,
        'stock_in_pcs' => 10,
    ]);

    Inventory::create([
        'name' => 'Paper Cups',
        'type' => 10,
        'stock_in_pcs' => 20,
    ]);

    \Pest\Laravel\actingAs($user)->getJson('api/v1/inventory')
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.name', 'Tea Leaves')
        ->assertJsonPath('1.name', 'Paper Cups');
});

test('Authenticated users can view an inventory stock.', function () {

    $user = User::factory()->create(['role' => UserRoleEnum::cashier]);

    $inventory = Inventory::create([
        'name' => 'Tea Leaves',
        'type' => 5,
        'stock_in_pcs' => 10,
    ]);

    \Pest\Laravel\actingAs($user)->getJson("api/v1/inventory/$inventory->id")
        ->assertOk()
        ->assertJsonPath('id', $inventory->id)
        ->assertJsonPath('name', 'Tea Leaves')
        ->assertJsonPath('type_code', 5)
        ->assertJsonPath('stock_in_pcs', 10);
});

test('Only admin can update inventory details.', function () {

    $adminUser = User::factory()->create(['role' => UserRoleEnum::admin]);
    $cashierUser = User::factory()->create(['role' => UserRoleEnum::cashier]);

    $inventory = Inventory::create([
        'name' => 'Tea Leaves',
        'type' => 5,
        'stock_in_pcs' => 10,
    ]);

    $payload = [
        'name' => 'Green Tea Leaves',
        'type' => 15,
    ];

    \Pest\Laravel\actingAs($cashierUser)->putJson("api/v1/inventory/$inventory->id", $payload)
        ->assertForbidden();

    \Pest\Laravel\actingAs($adminUser)->putJson("api/v1/inventory/$inventory->id", $payload)
        ->assertOk();

    \Pest\Laravel\assertDatabaseHas('inventories', [
        'id' => $inventory->id,
        'name' => 'Green Tea Leaves',
        'type' => 15,
        'stock_in_pcs' => 10,
    ]);
});

test('Only admin can adjust inventory stock.', function () {

    $adminUser = User::factory()->create(['role' => UserRoleEnum::admin]);
    $cashierUser = User::factory()->create(['role' => UserRoleEnum::cashier]);

    $inventory = Inventory::create([
        'name' => 'Tea Leaves',
        'type' => 5,
        'stock_in_pcs' => 10,
    ]);

    \Pest\Laravel\actingAs($cashierUser)->putJson("api/v1/inventory/$inventory->id/adjust-stock", [
        'stock_in_pcs' => 25,
    ])->assertForbidden();

    \Pest\Laravel\actingAs($adminUser)->putJson("api/v1/inventory/$inventory->id/adjust-stock", [
        'stock_in_pcs' => 25,
    ])->assertOk();

    \Pest\Laravel\assertDatabaseHas('inventories', [
        'id' => $inventory->id,
        'stock_in_pcs' => 25,
    ]);
});

test('Adjusting inventory stock rejects negative quantities.', function () {

    $adminUser = User::factory()->create(['role' => UserRoleEnum::admin]);

    $inventory = Inventory::create([
        'name' => 'Tea Leaves',
        'type' => 5,
        'stock_in_pcs' => 10,
    ]);

    \Pest\Laravel\actingAs($adminUser)->putJson("api/v1/inventory/$inventory->id/adjust-stock", [
        'stock_in_pcs' => -1,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('stock_in_pcs');

    \Pest\Laravel\assertDatabaseHas('inventories', [
        'id' => $inventory->id,
        'stock_in_pcs' => 10,
    ]);
});

test('Only admin can create inventory stocks. Unauthenticated and Cashier is not allowed.', function () {

    $adminUser = User::factory()->create(['role' => UserRoleEnum::admin]);
    $cashierUser = User::factory()->create(['role' => UserRoleEnum::cashier]);

    $payload = [
        'name' => 'Hotdog',
        'type' => 5,
        'stock_in_pcs' => 10,
    ];

    \Pest\Laravel\actingAs($adminUser)->postJson('api/v1/inventory', $payload)
        ->assertCreated();

    \Pest\Laravel\actingAs($cashierUser)->postJson('api/v1/inventory', $payload)
        ->assertForbidden();

    $this->postJson('api/v1/inventory', $payload)
        ->assertForbidden();
});

test('Only admin can delete inventory stocks. Unauthenticated and Cashier is not allowed.', function () {

    $adminUser = User::factory()->create(['role' => UserRoleEnum::admin]);
    $cashierUser = User::factory()->create(['role' => UserRoleEnum::cashier]);

    $payload = [
        'name' => 'Hotdog',
        'type' => 5,
        'stock_in_pcs' => 10,
    ];

    $inventoryData = Inventory::create($payload);

    \Pest\Laravel\actingAs($cashierUser)->delete("api/v1/inventory/$inventoryData->id")
        ->assertForbidden();

    $this->delete("api/v1/inventory/$inventoryData->id")
        ->assertForbidden();

    \Pest\Laravel\assertDatabaseHas('inventories', $payload);

    \Pest\Laravel\actingAs($adminUser)->delete("api/v1/inventory/$inventoryData->id")
        ->assertOk();

    \Pest\Laravel\assertDatabaseMissing('inventories', $payload);
});

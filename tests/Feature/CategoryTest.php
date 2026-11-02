<?php

use App\Enum\UserRoleEnum;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('cashiers can list categories.', function () {
    $user = User::factory()->create(['role' => UserRoleEnum::cashier]);
    Category::create(['name' => 'hot tea']);

    \Pest\Laravel\actingAs($user)->getJson('api/v1/categories')
        ->assertOk()
        ->assertJsonPath('0.name', 'Hot Tea');
});

test('cashiers can view a category.', function () {
    $user = User::factory()->create(['role' => UserRoleEnum::cashier]);
    $category = Category::create(['name' => 'hot tea']);

    \Pest\Laravel\actingAs($user)->getJson("api/v1/categories/$category->id")
        ->assertOk()
        ->assertJsonPath('id', $category->id)
        ->assertJsonPath('name', 'Hot Tea');
});

test('only admins can create categories.', function () {
    $admin = User::factory()->create(['role' => UserRoleEnum::admin]);
    $cashier = User::factory()->create(['role' => UserRoleEnum::cashier]);
    $payload = ['name' => '  HOT TEA  '];

    \Pest\Laravel\actingAs($admin)->postJson('api/v1/categories', $payload)
        ->assertCreated()
        ->assertJsonPath('name', 'Hot Tea');

    \Pest\Laravel\actingAs($cashier)->postJson('api/v1/categories', $payload)
        ->assertForbidden();

    $this->postJson('api/v1/categories', $payload)->assertForbidden();

    \Pest\Laravel\assertDatabaseHas('categories', ['name' => 'hot tea']);
});

test('category creation rejects duplicate names.', function () {
    $admin = User::factory()->create(['role' => UserRoleEnum::admin]);
    Category::create(['name' => 'hot tea']);

    \Pest\Laravel\actingAs($admin)->postJson('api/v1/categories', ['name' => 'HOT TEA'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('only admins can update categories.', function () {
    $admin = User::factory()->create(['role' => UserRoleEnum::admin]);
    $cashier = User::factory()->create(['role' => UserRoleEnum::cashier]);
    $category = Category::create(['name' => 'hot tea']);

    \Pest\Laravel\actingAs($cashier)->putJson("api/v1/categories/$category->id", ['name' => 'green tea'])
        ->assertForbidden();

    \Pest\Laravel\actingAs($admin)->putJson("api/v1/categories/$category->id", ['name' => 'GREEN TEA'])
        ->assertNoContent();

    \Pest\Laravel\assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'green tea',
    ]);
});

test('only admins can delete categories.', function () {
    $admin = User::factory()->create(['role' => UserRoleEnum::admin]);
    $cashier = User::factory()->create(['role' => UserRoleEnum::cashier]);
    $category = Category::create(['name' => 'hot tea']);

    \Pest\Laravel\actingAs($cashier)->deleteJson("api/v1/categories/$category->id")
        ->assertForbidden();

    \Pest\Laravel\actingAs($admin)->deleteJson("api/v1/categories/$category->id")
        ->assertNoContent();

    \Pest\Laravel\assertDatabaseMissing('categories', ['id' => $category->id]);
});

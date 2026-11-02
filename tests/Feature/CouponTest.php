<?php

use App\Enum\UserRoleEnum;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('only admins can manage coupons while cashiers can view them.', function () {
    $admin = User::factory()->create(['role' => UserRoleEnum::admin]);
    $cashier = User::factory()->create(['role' => UserRoleEnum::cashier]);
    $payload = [
        'code' => 'WELCOME10',
        'type' => 'percentage',
        'value' => 10,
        'minimum_purchase' => 100,
        'discount_cap' => 50,
        'is_active' => true,
    ];

    \Pest\Laravel\actingAs($cashier)->postJson('api/v1/coupons', $payload)->assertForbidden();
    \Pest\Laravel\actingAs($admin)->postJson('api/v1/coupons', $payload)->assertCreated();
    \Pest\Laravel\actingAs($cashier)->getJson('api/v1/coupons')->assertOk()->assertJsonPath('0.code', 'WELCOME10');

    $coupon = Coupon::first();
    \Pest\Laravel\actingAs($cashier)->deleteJson("api/v1/coupons/$coupon->id")->assertForbidden();
    \Pest\Laravel\actingAs($admin)->deleteJson("api/v1/coupons/$coupon->id")->assertNoContent();
    \Pest\Laravel\assertDatabaseMissing('coupons', ['id' => $coupon->id]);
});

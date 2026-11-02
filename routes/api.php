<?php

use App\Http\Controllers\InventoryController;
use App\Http\Controllers\Menu\CategoryController;
use App\Http\Controllers\Menu\MenuContentsController;
use App\Http\Controllers\Menu\MenuController;
use App\Http\Controllers\Menu\MenuOptionController;
use App\Http\Controllers\Order\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {

    /*
     * Inventory Operations Endpoints
     */
    Route::apiResource('inventories', InventoryController::class);
    Route::put('inventories/{inventory}/adjust-stock', [InventoryController::class, 'updateStock']);

    /*
     * Category Operations Endpoints
     */

    Route::apiResource('categories', CategoryController::class);

    /*
    * Menu Operations Endpoints
    */

    Route::apiResource('menus', MenuController::class);
    Route::put('menus/{menu}/publish', [MenuController::class, 'publish']);
    Route::put('menus/{menu}/deactivate', [MenuController::class, 'deactivate']);
    Route::put('menus/{menu}/flag-pulled-out', [MenuController::class, 'markPulledOut']);
    Route::put('menus/{menu}/mark-out-of-stock', [MenuController::class, 'markOutOfStock']);

    Route::get('menus/{menu}/options', [MenuOptionController::class, 'index']);
    Route::post('menus/{menu}/options', [MenuOptionController::class, 'store']);
    Route::get('menus/{menu}/options/{menu_option}/contents', [MenuContentsController::class, 'index'])
        ->scopeBindings();
    Route::post('menus/{menu}/options/{menu_option}/contents', [MenuContentsController::class, 'store'])
        ->scopeBindings();
    Route::get('menu-options/{menu_option}', [MenuOptionController::class, 'show']);
    Route::put('menu-options/{menu_option}', [MenuOptionController::class, 'update']);
    Route::delete('menu-options/{menu_option}', [MenuOptionController::class, 'destroy']);
    Route::put('menu-contents/{menu_content}', [MenuContentsController::class, 'update']);
    Route::delete('menu-contents/{menu_content}', [MenuContentsController::class, 'destroy']);

    Route::post('orders/calculate', [OrderController::class, 'calculate']);
    Route::post('orders/checkout', [OrderController::class, 'checkout']);
});

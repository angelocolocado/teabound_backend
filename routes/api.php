<?php

use App\Http\Controllers\InventoryController;
use App\Http\Controllers\Menu\CategoryController;
use App\Http\Controllers\Menu\MenuContentsController;
use App\Http\Controllers\Menu\MenuController;
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

    Route::get('menus/{menu}/contents', [MenuContentsController::class, 'getMenuContentsOfMenu']);
    Route::post('menus/{menu}/contents', [MenuContentsController::class, 'addMenuContent']);
    Route::delete('menus/menu-contents/{menu_content}', [MenuContentsController::class, 'destroyMenuContent']);
    Route::put('menus/menu-contents/{menu_content}', [MenuContentsController::class, 'patchMenuContent']);
});

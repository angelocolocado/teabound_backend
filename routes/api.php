<?php

use App\Http\Controllers\InventoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {

        /*
         * Inventory Operations Endpoints
         */
        Route::apiResource('inventory', InventoryController::class);
        Route::put('inventory/{inventory}/adjust-stock', [InventoryController::class, 'updateStock']);

        /*
         * Category Operations Endpoints
         */

        Route::apiResource('category', \App\Http\Controllers\Menu\CategoryController::class);


});

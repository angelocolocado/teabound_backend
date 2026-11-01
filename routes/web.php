<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/reset-password/{token}', function (Request $request, string $token) {
    return response()->json([
        'message' => 'Here are the token.',
        'token' => $token,
        'email' => $request->query('email'),
    ]);
})->name('password.reset');

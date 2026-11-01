<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/preview-email', function () {
    return view('emails.reset-password', [
        'url' => 'http://localhost:5500/reset-password.html?token=sample-token-123&email=test@example.com',
        'user' => (object) [
            'name' => 'Angelo Colocado'
        ]
    ]);
});

Route::get('/reset-password/{token}', function (Request $request, string $token) {
    return response()->json([
        'message' => 'Here are the token.',
        'token' => $token,
        'email' => $request->query('email')
    ]);
})->name('password.reset');

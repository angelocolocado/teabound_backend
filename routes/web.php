<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/hello', function (){
    return response()->json([
        "message" => "Hello from Laravel!",
        "data" => [
            "message" => "this is inside the data."
        ]
    ]);
});

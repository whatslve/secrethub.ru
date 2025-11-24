<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use app\Http\Controllers\Auth\AuthController;

Route::match(['get', 'post'], 'auth/telegram', [AuthController::class, 'loginTG']);
Route::middleware('auth:sanctum')->get('/user', function(Request $request) {
    return $request->user();
});
Route::get('/', function () {
    return 'API SOURCE';
});

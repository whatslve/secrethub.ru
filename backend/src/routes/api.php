<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;

Route::match(['get', 'post'], 'auth/telegram', [AuthController::class, 'loginTG']);
Route::middleware('auth:sanctum')->get('/user', function(Request $request) {
    return $request->user();
});
Route::get('/', function () {
    return 'API SOURCE';
});

Route::middleware('auth:sanctum')->post('/logout', function (Request $request) {
    // Удаляем токен, которым пользователь авторизован
    $request->user()->currentAccessToken()->delete();

    return response()->json(['message' => 'Logged out']);
});

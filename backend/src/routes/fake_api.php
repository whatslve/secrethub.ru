<?php
Route::post('fake/auth', [\App\Http\Controllers\Auth\FakeAuthController::class, 'loginTGFake']);

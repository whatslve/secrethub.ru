<?php

namespace app\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FakeAuthController extends Controller
{
    public function loginTGFake(Request $request)
    {
        $user = User::updateOrCreate(
            ['telegram_id' => '123test'],
            [
                'name' => 'admin_tester',
                'username' => 'admin_tester',
                //'photo_url' => $data['photo_url'] ?? null,
                'email' => 'none@none.com',
                'password' => bcrypt(Str::random(40)),
            ]
        );
        // Выдача Sanctum токена (если используешь Sanctum)
        $token = $user->createToken('api')->plainTextToken;

        Log::info('telegram.login_success', ['user_id' => $user->id, 'telegram_id' => '123test']);

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function loginTG(Request $request)
    {
        $data = $request->all();
        // Проверка подписи Telegram
        $hash = $data['hash'];
        unset($data['hash']);
        ksort($data);
        ksort($data);
        $check_array = [];
        foreach ($data as $key => $value) {
            $check_array[] = "$key=$value";
        }
        $check_string = implode("\n", $check_array);

        $secret_key = hash_hmac('sha256', env('TELEGRAM_BOT_TOKEN'), true);
        $calculated_hash = hash_hmac('sha256', $check_string, $secret_key);
        if (!hash_equals($hash, $calculated_hash)) {
            return response()->json(['error' => 'invalid telegram data'], 401);
        }

        // Найти или создать пользователя
        $user = User::firstOrCreate(
            ['telegram_id' => $data['id']],
            ['name' => $data['first_name'],
                'username' => $data['username'] ?? null,
                'api_token' => Str::random(60)
            ]);
        return response()->json([
            'token' => $user->createToken('api')->plainTextToken,
            'user' => $user
        ]);
    }
}

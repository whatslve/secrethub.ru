<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Login via Telegram Login Widget (GET or POST).
     */
    public function loginTG(Request $request)
    {
        // Разрешённые поля от виджета (telegram может присылать дополнительные — но основное это эти)
        $allowed = ['id', 'first_name', 'last_name', 'username', 'photo_url', 'auth_date', 'hash'];

        // Берём только поля, которые нас интересуют (если пришло через GET — Request::all() тоже сработает)
        $data = [];
        foreach ($allowed as $key) {
            if ($request->has($key)) {
                // Приводим к строке и не декодируем дополнительно — Laravel уже декодировал query params
                $data[$key] = (string) $request->input($key);
            }
        }

        Log::debug('telegram.payload', $data);

        // must have hash
        if (empty($data['hash'])) {
            Log::warning('telegram.missing_hash', $data);
            return response()->json(['error' => 'missing hash'], 400);
        }

        $receivedHash = $data['hash'];
        unset($data['hash']); // убираем hash перед формированием строки

        // проверяем длительность жизни: auth_date должен быть недавним (рекомендация Telegram <= 24ч)
        if (empty($data['auth_date']) || (time() - (int) $data['auth_date']) > 86400) {
            Log::warning('telegram.auth_date_expired', ['auth_date' => $data['auth_date'] ?? null]);
            return response()->json(['error' => 'auth data expired'], 403);
        }

        // сортируем по ключам (ключи, не строки), затем формируем массив "key=value"
        ksort($data, SORT_STRING);

        $checkParts = [];
        foreach ($data as $k => $v) {
            // Telegram требует точный формат key=value, без лишних пробелов/перекодировок
            $checkParts[] = $k . '=' . $v;
        }

        // data_check_string: пары через "\n" (LF)
        $checkString = implode("\n", $checkParts);

        // secret_key = SHA256(bot_token) -> бинарный вывод
        $botToken = config('services.telegram.bot_token') ?: env('TELEGRAM_BOT_TOKEN');
        if (empty($botToken)) {
            Log::error('telegram.missing_bot_token');
            return response()->json(['error' => 'server misconfigured'], 500);
        }

        $secretKeyBinary = hash('sha256', $botToken, true);
        // HMAC-SHA256 of data_check_string using secretKeyBinary
        $calculatedHash = hash_hmac('sha256', $checkString, $secretKeyBinary);

        // Сравниваем безопасно
        if (!hash_equals($calculatedHash, $receivedHash)) {
            Log::warning('telegram.signature_mismatch', [
                'calc' => $calculatedHash,
                'recv' => $receivedHash,
                'checkString' => $checkString,
            ]);
            return response()->json(['error' => 'invalid telegram data'], 401);
        }

        // Подписка прошла — создаём или обновляем пользователя
        $telegramId = $data['id'] ?? null;
        if (!$telegramId) {
            Log::error('telegram.missing_id', ['data' => $data]);
            return response()->json(['error' => 'missing telegram id'], 400);
        }

        $user = User::updateOrCreate(
            ['telegram_id' => $telegramId],
            [
                'name' => trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
                'username' => $data['username'] ?? null,
                //'photo_url' => $data['photo_url'] ?? null,
                'email' => 'none@none.com',
                'password' => bcrypt(Str::random(40)),
            ]
        );
        // Выдача Sanctum токена (если используешь Sanctum)
        $token = $user->createToken('api')->plainTextToken;

        Log::info('telegram.login_success', ['user_id' => $user->id, 'telegram_id' => $telegramId]);

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }
}

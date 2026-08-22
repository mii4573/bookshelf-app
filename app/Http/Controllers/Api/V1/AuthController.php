<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * API ログイン (トークン発行)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        // ユーザーが存在しない、またはパスワードが一致しない場合
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['提供された認証情報が正しくありません。'],
            ]);
        }

        // Sanctum APIトークンの発行
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'ログインに成功しました。',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * API ログアウト (トークン破棄)
     */
    public function logout(Request $request)
    {
        // リクエストを送ってきた現在のトークンを削除
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'ログアウトし、トークンを破棄しました。',
        ]);
    }
}

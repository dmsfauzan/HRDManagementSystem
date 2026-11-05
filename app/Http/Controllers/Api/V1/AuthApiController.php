<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ApiTokenService;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthApiController extends Controller
{
    public function __construct(private readonly ApiTokenService $tokens) {}

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Email atau password salah.'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Akun tidak aktif. Hubungi administrator.'], 403);
        }

        if ($user->two_factor_enabled) {
            return response()->json([
                'message' => 'Akun ini mengaktifkan verifikasi dua faktor. Silakan login melalui aplikasi web.',
            ], 422);
        }

        [$token, $plain] = $this->tokens->issue(
            $user,
            $data['device_name'] ?? 'Login API',
            ['*'],
            now()->addDays(30)
        );

        AuditLogger::log('api_token.login', $token, [], ['name' => $token->name]);

        return response()->json([
            'token_type' => 'Bearer',
            'token' => $plain,
            'expires_at' => $token->expires_at?->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Support\Str;

class ApiTokenService
{
    /**
     * Terbitkan token baru. Mengembalikan [ApiToken, string token mentah].
     *
     * @return array{0: ApiToken, 1: string}
     */
    public function issue(User $user, string $name, array $abilities = ['*'], ?\DateTimeInterface $expiresAt = null): array
    {
        $plain = Str::random(64);

        $token = ApiToken::create([
            'user_id' => $user->id,
            'name' => $name,
            'token' => hash('sha256', $plain),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        return [$token, $plain];
    }

    public function resolve(?string $plain): ?ApiToken
    {
        if (! $plain) {
            return null;
        }

        $token = ApiToken::with('user')
            ->where('token', hash('sha256', $plain))
            ->first();

        if (! $token || $token->isExpired()) {
            return null;
        }

        $token->forceFill(['last_used_at' => now()])->save();

        return $token;
    }
}

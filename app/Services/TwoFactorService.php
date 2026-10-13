<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\TwoFactorCode;
use Illuminate\Support\Facades\Hash;

class TwoFactorService
{
    public const TTL_MINUTES = 10;

    public function send(User $user): void
    {
        // Hindari kirim ulang dalam 60 detik terakhir (anti-spam).
        if ($user->two_factor_expires_at && $user->two_factor_expires_at->gt(now()->addMinutes(self::TTL_MINUTES - 1))) {
            return;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->forceFill([
            'two_factor_code' => Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ])->save();

        $user->notify(new TwoFactorCode($code));
    }

    public function verify(User $user, string $code): bool
    {
        if (! $user->two_factor_code || ! $user->two_factor_expires_at) {
            return false;
        }

        if ($user->two_factor_expires_at->isPast()) {
            return false;
        }

        if (! Hash::check($code, $user->two_factor_code)) {
            return false;
        }

        $user->forceFill([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ])->save();

        return true;
    }
}

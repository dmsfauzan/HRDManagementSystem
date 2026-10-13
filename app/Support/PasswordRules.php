<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class PasswordRules
{
    /**
     * Aturan password standar aplikasi (minimal 12 karakter, huruf besar-kecil, angka).
     *
     * @return array<int, mixed>
     */
    public static function default(): array
    {
        return [
            'required',
            'string',
            Password::min(12)->letters()->mixedCase()->numbers(),
        ];
    }

    /**
     * Aturan untuk penggantian password (dengan konfirmasi).
     *
     * @return array<int, mixed>
     */
    public static function confirmed(): array
    {
        return [
            'required',
            'string',
            'confirmed',
            Password::min(12)->letters()->mixedCase()->numbers(),
        ];
    }
}

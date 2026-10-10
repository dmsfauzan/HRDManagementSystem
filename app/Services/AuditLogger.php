<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public static function log(string $action, ?Model $auditable = null, array $oldValues = [], array $newValues = []): void
    {
        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'auditable_type' => $auditable?->getMorphClass(),
                'auditable_id' => $auditable?->getKey(),
                'old_values' => self::sanitize($oldValues) ?: null,
                'new_values' => self::sanitize($newValues) ?: null,
                'ip_address' => self::requestIp(),
                'user_agent' => self::userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Audit tidak boleh menggagalkan alur utama.
            report($e);
        }
    }

    private static function sanitize(array $values): array
    {
        $sensitive = ['password', 'remember_token', 'attachment_path', 'photo_path'];

        return collect($values)
            ->except($sensitive)
            ->reject(fn ($value) => $value instanceof \Closure)
            ->all();
    }

    private static function requestIp(): ?string
    {
        return app()->runningInConsole() ? null : request()->ip();
    }

    private static function userAgent(): ?string
    {
        return app()->runningInConsole() ? null : substr((string) request()->userAgent(), 0, 255);
    }
}

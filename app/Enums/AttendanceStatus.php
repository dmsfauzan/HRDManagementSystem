<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Late = 'late';
    case Absent = 'absent';
    case Leave = 'leave';
    case Permit = 'permit';
    case Holiday = 'holiday';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Hadir',
            self::Late => 'Terlambat',
            self::Absent => 'Tidak Hadir',
            self::Leave => 'Cuti',
            self::Permit => 'Izin',
            self::Holiday => 'Libur',
        };
    }

    public static function presentLike(): array
    {
        return [self::Present->value, self::Late->value];
    }
}

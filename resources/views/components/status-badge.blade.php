@props(['status'])
@php
    $map = [
        'active' => ['Aktif', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        'inactive' => ['Nonaktif', 'bg-slate-100 text-slate-600 border-slate-200'],
        'resigned' => ['Resign', 'bg-red-50 text-red-700 border-red-200'],
        'pending' => ['Menunggu', 'bg-amber-50 text-amber-700 border-amber-200'],
        'approved' => ['Disetujui', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        'rejected' => ['Ditolak', 'bg-red-50 text-red-700 border-red-200'],
        'cancelled' => ['Dibatalkan', 'bg-slate-100 text-slate-600 border-slate-200'],
        'present' => ['Hadir', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        'late' => ['Terlambat', 'bg-amber-50 text-amber-700 border-amber-200'],
        'absent' => ['Tidak Hadir', 'bg-red-50 text-red-700 border-red-200'],
        'leave' => ['Cuti', 'bg-blue-50 text-blue-700 border-blue-200'],
        'permit' => ['Izin', 'bg-violet-50 text-violet-700 border-violet-200'],
        'permanent' => ['Tetap', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        'contract' => ['Kontrak', 'bg-blue-50 text-blue-700 border-blue-200'],
        'intern' => ['Magang', 'bg-violet-50 text-violet-700 border-violet-200'],
        'probation' => ['Percobaan', 'bg-amber-50 text-amber-700 border-amber-200'],
    ];
    [$text, $classes] = $map[$status] ?? [ucfirst((string) $status), 'bg-slate-100 text-slate-600 border-slate-200'];
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border $classes"]) }}>{{ $text }}</span>

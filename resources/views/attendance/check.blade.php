<x-app-layout>
    <x-slot name="header">Absensi Hari Ini</x-slot>

    <x-page-header title="Absensi Hari Ini" :subtitle="$employee->full_name . ' — ' . $employee->employee_id" />

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-card title="Check-in">
            @if($record?->check_in_at)
                <p class="text-sm text-slate-600">Anda sudah check-in pada <span class="font-semibold text-slate-900">{{ $record->check_in_at->timezone(tz())->format('H:i') }}</span>.</p>
                <p class="text-sm text-slate-600 mt-2">Status: <x-status-badge :status="$record->status" />
                    @if($record->status === 'late') <span class="text-amber-700">Terlambat {{ $record->late_minutes }} menit.</span> @endif
                </p>
            @else
                <p class="text-sm text-slate-600 mb-4">Waktu server saat ini: <span class="font-semibold text-slate-900">{{ now()->timezone(tz())->format('H:i') }}</span> WIB.</p>
                <form method="POST" action="{{ route('attendance.store') }}">
                    @csrf
                    <x-primary-button type="submit">Check-in Sekarang</x-primary-button>
                </form>
            @endif
        </x-card>

        <x-card title="Check-out">
            @if($record?->check_out_at)
                <p class="text-sm text-slate-600">Anda sudah check-out pada <span class="font-semibold text-slate-900">{{ $record->check_out_at->timezone(tz())->format('H:i') }}</span>.</p>
            @elseif($record?->check_in_at)
                <form method="POST" action="{{ route('attendance.checkout') }}">
                    @csrf
                    <x-primary-button type="submit">Check-out Sekarang</x-primary-button>
                </form>
            @else
                <x-empty-state title="Check-in dulu" description="Lakukan check-in sebelum bisa check-out." />
            @endif
        </x-card>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">Detail Absensi</x-slot>

    <x-page-header :title="$record->employee->full_name" :subtitle="$record->work_date->translatedFormat('l, d F Y')" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <x-card title="Data Absensi" class="lg:col-span-2">
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-slate-500">Jam Masuk</dt><dd class="text-slate-900 font-medium">{{ $record->check_in_at?->timezone('Asia/Jakarta')->format('H:i') ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Jam Keluar</dt><dd class="text-slate-900 font-medium">{{ $record->check_out_at?->timezone('Asia/Jakarta')->format('H:i') ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Status</dt><dd><x-status-badge :status="$record->status" /></dd></div>
                <div><dt class="text-slate-500">Keterlambatan</dt><dd class="text-slate-900 font-medium">{{ $record->late_minutes }} menit</dd></div>
                <div><dt class="text-slate-500">Durasi Kerja</dt><dd class="text-slate-900 font-medium">{{ intdiv($record->work_minutes, 60) }} jam {{ $record->work_minutes % 60 }} menit</dd></div>
                <div><dt class="text-slate-500">IP Masuk</dt><dd class="text-slate-900 font-medium">{{ $record->check_in_ip ?? '—' }}</dd></div>
            </dl>
            @if($record->notes)
                <p class="mt-4 text-sm text-slate-600">{{ $record->notes }}</p>
            @endif
        </x-card>

        <x-card title="Riwayat Koreksi">
            <div class="space-y-3">
                @forelse($record->corrections as $correction)
                    <div class="border border-slate-100 rounded-lg p-3">
                        <div class="flex items-center justify-between">
                            <x-status-badge :status="$correction->status" />
                            <span class="text-xs text-slate-400">{{ $correction->created_at->translatedFormat('d M Y H:i') }}</span>
                        </div>
                        <p class="text-xs text-slate-600 mt-2">{{ $correction->reason }}</p>
                    </div>
                @empty
                    <x-empty-state title="Belum ada koreksi" />
                @endforelse
            </div>
        </x-card>
    </div>
</x-app-layout>

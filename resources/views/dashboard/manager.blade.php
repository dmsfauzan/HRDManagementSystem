<x-app-layout>
    <x-slot name="header">Dashboard Manager</x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-stat-card label="Anggota Tim" :value="$teamCount" accent="emerald" />
        <x-stat-card label="Hadir Hari Ini" :value="$teamPresentToday" accent="blue" :hint="'Per ' . $today->translatedFormat('d M Y')" />
        <x-stat-card label="Menunggu Persetujuan" :value="$teamPendingLeaves" accent="amber" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-6">
        <x-card title="Anggota Tim">
            <div class="space-y-3">
                @forelse($members as $member)
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center text-xs font-semibold text-slate-600">{{ strtoupper(substr($member->full_name, 0, 1)) }}</div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-800 truncate">{{ $member->full_name }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ $member->position->name ?? '-' }} &middot; {{ $member->department->name ?? '-' }}</p>
                        </div>
                    </div>
                @empty
                    <x-empty-state title="Belum ada anggota tim" description="Karyawan belum ditautkan ke Anda sebagai atasan." />
                @endforelse
            </div>
        </x-card>

        <x-card title="Pengajuan Menunggu Persetujuan">
            <div class="space-y-3">
                @forelse($pendingLeaves as $leave)
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-800 truncate">{{ $leave->employee->full_name ?? '-' }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ $leave->leaveType->name ?? '-' }} &middot; {{ $leave->start_date->translatedFormat('d M') }} &ndash; {{ $leave->end_date->translatedFormat('d M Y') }}</p>
                        </div>
                        <x-status-badge :status="$leave->status" />
                    </div>
                @empty
                    <x-empty-state title="Tidak ada pengajuan menunggu" />
                @endforelse
            </div>
        </x-card>
    </div>
</x-app-layout>

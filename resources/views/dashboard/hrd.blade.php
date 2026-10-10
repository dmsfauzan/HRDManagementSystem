<x-app-layout>
    <x-slot name="header">Dashboard HRD</x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
        <x-stat-card label="Karyawan Aktif" :value="$totalActiveEmployees" accent="emerald">
            <x-slot:icon><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5V4H2v16h5m10 0v-2a3 3 0 00-3-3H10a3 3 0 00-3 3v2m8 0H9"/></svg></x-slot:icon>
        </x-stat-card>
        <x-stat-card label="Hadir Hari Ini" :value="$presentToday" accent="blue" :hint="'Per ' . $today->translatedFormat('d M Y')">
            <x-slot:icon><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot:icon>
        </x-stat-card>
        <x-stat-card label="Terlambat" :value="$lateToday" accent="amber">
            <x-slot:icon><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot:icon>
        </x-stat-card>
        <x-stat-card label="Tidak Hadir" :value="$absentToday" accent="red" :hint="$isHoliday ? 'Hari libur' : 'Hari kerja'">
            <x-slot:icon><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2 2m2-2l2 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot:icon>
        </x-stat-card>
        <x-stat-card label="Cuti Menunggu" :value="$pendingLeaves" accent="violet">
            <x-slot:icon><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7H3v12a2 2 0 002 2z"/></svg></x-slot:icon>
        </x-stat-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-6">
        <x-card title="Karyawan per Departemen" class="lg:col-span-2">
            @php $maxCount = max(1, $employeesPerDepartment->max('employees_count')); @endphp
            @forelse($employeesPerDepartment as $dept)
                <div class="mb-3 last:mb-0">
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="text-slate-700">{{ $dept->name }}</span>
                        <span class="font-medium text-slate-900">{{ $dept->employees_count }}</span>
                    </div>
                    <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ round($dept->employees_count / $maxCount * 100) }}%"></div>
                    </div>
                </div>
            @empty
                <x-empty-state title="Belum ada departemen" />
            @endforelse
        </x-card>

        <x-card title="Pengajuan Cuti Terbaru">
            <div class="space-y-3">
                @forelse($recentLeaves as $leave)
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-800 truncate">{{ $leave->employee->full_name ?? '-' }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ $leave->leaveType->name ?? '-' }} &middot; {{ $leave->start_date->translatedFormat('d M') }}</p>
                        </div>
                        <x-status-badge :status="$leave->status" />
                    </div>
                @empty
                    <x-empty-state title="Belum ada pengajuan" />
                @endforelse
            </div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-6">
        <x-card title="Karyawan Terbaru">
            <div class="space-y-3">
                @forelse($recentEmployees as $emp)
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center text-xs font-semibold text-slate-600">{{ strtoupper(substr($emp->full_name, 0, 1)) }}</div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-800 truncate">{{ $emp->full_name }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ $emp->position->name ?? '-' }} &middot; {{ $emp->department->name ?? '-' }}</p>
                        </div>
                        <span class="ml-auto text-xs text-slate-400">{{ $emp->employee_id }}</span>
                    </div>
                @empty
                    <x-empty-state title="Belum ada karyawan" description="Tambahkan karyawan untuk mulai mengelola data HR." />
                @endforelse
            </div>
        </x-card>

        <x-card title="Absensi Terbaru">
            <div class="space-y-3">
                @forelse($recentAttendance as $att)
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-800 truncate">{{ $att->employee->full_name ?? '-' }}</p>
                            <p class="text-xs text-slate-500">{{ $att->work_date->translatedFormat('d M Y') }}</p>
                        </div>
                        <x-status-badge :status="$att->status" />
                    </div>
                @empty
                    <x-empty-state title="Belum ada absensi" />
                @endforelse
            </div>
        </x-card>
    </div>
</x-app-layout>

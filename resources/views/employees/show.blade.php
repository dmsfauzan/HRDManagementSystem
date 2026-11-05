<x-app-layout>
    <x-slot name="header">Detail Karyawan</x-slot>

    <x-breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Karyawan', 'url' => route('employees.index')],
        ['label' => $employee->full_name],
    ]" />

    <x-page-header :title="$employee->full_name" :subtitle="'NIP ' . $employee->employee_id">
        <x-slot:actions>
            @can('update', $employee)
                <a href="{{ route('employees.edit', $employee) }}">
                    <x-secondary-button>Ubah</x-secondary-button>
                </a>
            @endcan
            @can('deactivate', $employee)
                @if($employee->employment_status === 'active')
                    <form method="POST" action="{{ route('employees.deactivate', $employee) }}" onsubmit="return confirm('Nonaktifkan karyawan ini? Riwayat absensi dan cuti tetap tersimpan.')">
                        @csrf @method('PATCH')
                        <x-secondary-button type="submit">Nonaktifkan</x-secondary-button>
                    </form>
                    <form method="POST" action="{{ route('employees.offboard', $employee) }}" onsubmit="return confirm('Proses offboarding (resign) karyawan ini? Akun akan dinonaktifkan.')">
                        @csrf @method('PATCH')
                        <input type="hidden" name="reason" value="Resign">
                        <x-secondary-button type="submit">Offboard</x-secondary-button>
                    </form>
                @else
                    <form method="POST" action="{{ route('employees.activate', $employee) }}">
                        @csrf @method('PATCH')
                        <x-primary-button type="submit">Aktifkan</x-primary-button>
                    </form>
                @endif
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <x-card title="Profil" class="lg:col-span-1">
            <div class="flex flex-col items-center text-center">
                @if($employee->photo_path)
                    <img src="{{ route('employees.photo', $employee) }}" alt="" class="w-24 h-24 rounded-full object-cover">
                @else
                    <span class="w-24 h-24 rounded-full bg-slate-100 flex items-center justify-center text-2xl font-semibold text-slate-500">{{ strtoupper(substr($employee->full_name, 0, 1)) }}</span>
                @endif
                <p class="mt-3 font-semibold text-slate-900">{{ $employee->full_name }}</p>
                <p class="text-sm text-slate-500">{{ $employee->position->name ?? 'Belum ada jabatan' }}</p>
                <div class="mt-3 flex items-center gap-2">
                    <x-status-badge :status="$employee->employment_status" />
                    <x-status-badge :status="$employee->employment_type" />
                </div>
            </div>
            <dl class="mt-6 space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Email</dt><dd class="text-slate-800 text-right">{{ $employee->email ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Telepon</dt><dd class="text-slate-800 text-right">{{ $employee->phone ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Departemen</dt><dd class="text-slate-800 text-right">{{ $employee->department->name ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Atasan</dt><dd class="text-slate-800 text-right">{{ $employee->manager->full_name ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Mulai Kerja</dt><dd class="text-slate-800 text-right">{{ $employee->join_date?->translatedFormat('d M Y') }}</dd></div>
                @if($employee->contract_end_date)
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Akhir Kontrak</dt><dd class="text-slate-800 text-right">{{ $employee->contract_end_date->translatedFormat('d M Y') }}</dd></div>
                @endif
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Jadwal Kerja</dt><dd class="text-slate-800 text-right">{{ $employee->workSchedule->name ?? '—' }}</dd></div>
            </dl>
        </x-card>

        <div class="lg:col-span-2 space-y-6">
            <x-card title="Anggota Tim (Bawahan)">
                <div class="space-y-3">
                    @forelse($subordinates as $member)
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-xs font-semibold text-slate-500">{{ strtoupper(substr($member->full_name, 0, 1)) }}</span>
                            <div class="min-w-0">
                                <a href="{{ route('employees.show', $member) }}" class="block text-sm font-medium text-slate-800 hover:text-emerald-700">{{ $member->full_name }}</a>
                                <p class="text-xs text-slate-500">{{ $member->position->name ?? '—' }}</p>
                            </div>
                            <x-status-badge :status="$member->employment_status" class="ml-auto" />
                        </div>
                    @empty
                        <x-empty-state title="Tidak ada bawahan langsung" />
                    @endforelse
                </div>
            </x-card>

            <x-card title="Ringkasan Kehadiran & Cuti">
                <p class="text-sm text-slate-500">Rekap absensi dan cuti akan tersedia pada modul Absensi (P2) dan Cuti (P3).</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-4">
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Total Absensi</p>
                        <p class="text-lg font-semibold text-slate-900">{{ $employee->attendanceRecords()->count() }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Total Pengajuan Cuti</p>
                        <p class="text-lg font-semibold text-slate-900">{{ $employee->leaveRequests()->count() }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Saldo Cuti Tersedia</p>
                        <p class="text-lg font-semibold text-slate-900">{{ rtrim(rtrim(number_format($employee->leaveBalances()->sum('balance'), 2, ',', '.'), '0'), ',') }}</p>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>

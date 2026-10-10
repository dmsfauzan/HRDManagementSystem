<x-app-layout>
    <x-slot name="header">Dashboard Karyawan</x-slot>

    @if(! $employee)
        <x-card>
            <x-empty-state title="Profil karyawan belum tertaut"
                description="Akun Anda belum dihubungkan ke data karyawan. Hubungi Admin HRD untuk melengkapi data." />
        </x-card>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <x-stat-card label="Status Hari Ini" :value="$attendanceToday ? \Illuminate\Support\Str::headline($attendanceToday->status) : 'Belum Absen'" accent="emerald" />
            <x-stat-card label="Jam Masuk" :value="$attendanceToday?->check_in_at?->timezone('Asia/Jakarta')->format('H:i') ?? '--:--'" accent="blue" />
            <x-stat-card label="Jam Keluar" :value="$attendanceToday?->check_out_at?->timezone('Asia/Jakarta')->format('H:i') ?? '--:--'" accent="violet" />
            <x-stat-card label="Saldo Cuti" :value="$leaveBalances->sum('balance')" :hint="'Tahun ' . $today->format('Y')" accent="amber" />
        </div>

        <x-card class="mt-6" title="Absensi Hari Ini">
            <div class="flex flex-wrap items-center gap-3">
                <p class="text-sm text-slate-600">
                    @if($attendanceToday?->check_out_at)
                        Absensi hari ini sudah lengkap.
                    @elseif($attendanceToday?->check_in_at)
                        Sudah check-in pukul {{ $attendanceToday->check_in_at->timezone('Asia/Jakarta')->format('H:i') }}.
                    @else
                        Anda belum melakukan check-in hari ini.
                    @endif
                </p>
                <div class="ml-auto flex gap-2">
                    @if(! $attendanceToday?->check_in_at)
                        <form method="POST" action="{{ route('attendance.store') }}">@csrf
                            <x-primary-button type="submit">Check-in</x-primary-button>
                        </form>
                    @elseif(! $attendanceToday?->check_out_at)
                        <form method="POST" action="{{ route('attendance.checkout') }}">@csrf
                            <x-primary-button type="submit">Check-out</x-primary-button>
                        </form>
                    @endif
                    <a href="{{ route('attendance.index') }}"><x-secondary-button type="button">Lihat Absensi</x-secondary-button></a>
                </div>
            </div>
        </x-card>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-6">
            <x-card title="Ringkasan Kehadiran" class="lg:col-span-2">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                                <th class="py-2 pr-4 font-medium">Tanggal</th>
                                <th class="py-2 pr-4 font-medium">Masuk</th>
                                <th class="py-2 pr-4 font-medium">Keluar</th>
                                <th class="py-2 pr-4 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($recentAttendance as $att)
                                <tr>
                                    <td class="py-2 pr-4 text-slate-700">{{ $att->work_date->translatedFormat('d M Y') }}</td>
                                    <td class="py-2 pr-4 text-slate-600">{{ $att->check_in_at?->timezone('Asia/Jakarta')->format('H:i') ?? '-' }}</td>
                                    <td class="py-2 pr-4 text-slate-600">{{ $att->check_out_at?->timezone('Asia/Jakarta')->format('H:i') ?? '-' }}</td>
                                    <td class="py-2 pr-4"><x-status-badge :status="$att->status" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><x-empty-state title="Belum ada riwayat absensi" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            <x-card title="Saldo Cuti">
                <div class="space-y-3">
                    @forelse($leaveBalances as $balance)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-700">{{ $balance->leaveType->name ?? '-' }}</span>
                            <span class="text-sm font-semibold text-slate-900">{{ rtrim(rtrim(number_format($balance->balance, 2, ',', '.'), '0'), ',') }} hari</span>
                        </div>
                    @empty
                        <x-empty-state title="Belum ada saldo cuti" />
                    @endforelse
                </div>
                <div class="mt-4 flex gap-2">
                    <a href="{{ route('leave.create') }}" class="text-sm text-emerald-700 hover:underline">Ajukan cuti</a>
                    <a href="{{ route('leave.balances.index') }}" class="text-sm text-slate-500 hover:underline">Lihat saldo</a>
                </div>
            </x-card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-6">
            <x-card title="Pengajuan Terbaru">
                <div class="space-y-3">
                    @forelse($recentLeaves as $leave)
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-800 truncate">{{ $leave->leaveType->name ?? '-' }}</p>
                                <p class="text-xs text-slate-500">{{ $leave->start_date->translatedFormat('d M') }} &ndash; {{ $leave->end_date->translatedFormat('d M Y') }}</p>
                            </div>
                            <x-status-badge :status="$leave->status" />
                        </div>
                    @empty
                        <x-empty-state title="Belum ada pengajuan" />
                    @endforelse
                </div>
            </x-card>

            <x-card title="Notifikasi">
                <div class="space-y-3">
                    @forelse($notifications as $notif)
                        <div class="flex items-start gap-3">
                            <span class="mt-1 w-2 h-2 rounded-full {{ $notif->read_at ? 'bg-slate-300' : 'bg-emerald-500' }}"></span>
                            <div class="min-w-0">
                                <p class="text-sm text-slate-700">{{ $notif->data['message'] ?? 'Notifikasi baru' }}</p>
                                <p class="text-xs text-slate-400">{{ $notif->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    @empty
                        <x-empty-state title="Tidak ada notifikasi" />
                    @endforelse
                </div>
            </x-card>
        </div>
    @endif
</x-app-layout>

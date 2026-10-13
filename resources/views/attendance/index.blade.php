<x-app-layout>
    <x-slot name="header">Absensi</x-slot>

    <x-page-header title="Absensi" subtitle="Kehadiran harian, rekap, dan koreksi.">
        <x-slot:actions>
            @can('viewAny', \App\Models\AttendanceCorrection::class)
                <a href="{{ route('attendance.corrections.index') }}"><x-secondary-button>Koreksi Absensi</x-secondary-button></a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @if(auth()->user()->employee)
        <x-card class="mb-6" title="Absensi Saya Hari Ini">
            <div class="flex flex-wrap items-center gap-4">
                <div class="text-sm">
                    <p class="text-slate-500">Jam Masuk</p>
                    <p class="font-semibold text-slate-900">{{ $todayRecord?->check_in_at?->timezone(tz())->format('H:i') ?? '--:--' }}</p>
                </div>
                <div class="text-sm">
                    <p class="text-slate-500">Jam Keluar</p>
                    <p class="font-semibold text-slate-900">{{ $todayRecord?->check_out_at?->timezone(tz())->format('H:i') ?? '--:--' }}</p>
                </div>
                <div class="text-sm">
                    <p class="text-slate-500">Status</p>
                    @if($todayRecord)
                        <x-status-badge :status="$todayRecord->status" />
                    @else
                        <span class="text-slate-400 text-sm">Belum absen</span>
                    @endif
                </div>
                <div class="ml-auto flex gap-2">
                    @if(! $todayRecord?->check_in_at)
                        <form method="POST" action="{{ route('attendance.store') }}">@csrf
                            <x-primary-button type="submit">Check-in</x-primary-button>
                        </form>
                    @elseif(! $todayRecord?->check_out_at)
                        <form method="POST" action="{{ route('attendance.checkout') }}">@csrf
                            <x-primary-button type="submit">Check-out</x-primary-button>
                        </form>
                    @endif
                </div>
            </div>
        </x-card>
    @endif

    <x-card>
        <form method="GET" action="{{ route('attendance.index') }}" class="flex flex-wrap items-end gap-2 mb-4">
            <input type="hidden" name="period" value="{{ $period }}">
            @if($period === 'daily')
                <div>
                    <x-input-label for="date" value="Tanggal" />
                    <x-text-input id="date" name="date" type="date" class="mt-1" :value="request('date', now()->timezone(tz())->toDateString())" />
                </div>
            @elseif($period === 'monthly')
                <div>
                    <x-input-label for="month" value="Bulan" />
                    <x-text-input id="month" name="month" type="month" class="mt-1" :value="request('month', now()->format('Y-m'))" />
                </div>
            @else
                <div>
                    <x-input-label for="from" value="Dari" />
                    <x-text-input id="from" name="from" type="date" class="mt-1" :value="request('from')" />
                </div>
                <div>
                    <x-input-label for="to" value="Sampai" />
                    <x-text-input id="to" name="to" type="date" class="mt-1" :value="request('to')" />
                </div>
            @endif

            <div>
                <x-input-label for="view" value="Periode" />
                <x-select-input id="view" name="period" class="mt-1" onchange="this.form.submit()">
                    <option value="daily" @selected($period === 'daily')>Harian</option>
                    <option value="monthly" @selected($period === 'monthly')>Bulanan</option>
                    <option value="range" @selected($period === 'range')>Rentang</option>
                </x-select-input>
            </div>

            @if($departments->isNotEmpty())
                <div>
                    <x-input-label for="department_id" value="Departemen" />
                    <x-select-input id="department_id" name="department_id" class="mt-1">
                        <option value="">Semua</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </x-select-input>
                </div>
            @endif

            <div>
                <x-input-label for="status" value="Status" />
                <x-select-input id="status" name="status" class="mt-1">
                    <option value="">Semua</option>
                    @foreach(['present' => 'Hadir', 'late' => 'Terlambat', 'absent' => 'Tidak Hadir', 'leave' => 'Cuti', 'permit' => 'Izin'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </x-select-input>
            </div>

            <x-secondary-button type="submit">Terapkan</x-secondary-button>
        </form>

        @if($records->isEmpty())
            <x-empty-state title="Tidak ada data absensi" description="Belum ada absensi pada filter yang dipilih." />
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                            <th class="py-2 px-2 font-medium">Tanggal</th>
                            <th class="py-2 pr-4 font-medium">Karyawan</th>
                            <th class="py-2 pr-4 font-medium">Masuk</th>
                            <th class="py-2 pr-4 font-medium">Keluar</th>
                            <th class="py-2 pr-4 font-medium">Telat</th>
                            <th class="py-2 pr-4 font-medium">Status</th>
                            <th class="py-2 pr-4 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($records as $record)
                            <tr class="hover:bg-slate-50/60">
                                <td class="py-3 px-2 text-slate-700">{{ $record->work_date->translatedFormat('d M Y') }}</td>
                                <td class="py-3 pr-4">
                                    <span class="block font-medium text-slate-800">{{ $record->employee->full_name ?? '—' }}</span>
                                    <span class="block text-xs text-slate-500">{{ $record->employee->department->name ?? '—' }}</span>
                                </td>
                                <td class="py-3 pr-4 text-slate-600">{{ $record->check_in_at?->timezone(tz())->format('H:i') ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $record->check_out_at?->timezone(tz())->format('H:i') ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $record->late_minutes > 0 ? $record->late_minutes.' mnt' : '—' }}</td>
                                <td class="py-3 pr-4"><x-status-badge :status="$record->status" /></td>
                                <td class="py-3 pr-4 text-right">
                                    <a href="{{ route('attendance.show', $record) }}" class="text-sm text-slate-600 hover:text-slate-900">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $records->links() }}</div>
        @endif
    </x-card>
</x-app-layout>

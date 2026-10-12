<x-app-layout>
    <x-slot name="header">Rekap Absensi</x-slot>

    <x-page-header title="Rekap Absensi" subtitle="Kehadiran, keterlambatan, dan status lainnya.">
        <x-slot:actions>
            <a href="{{ route('reports.attendance', request()->query() + ['format' => 'xlsx']) }}"><x-secondary-button>Excel</x-secondary-button></a>
            <a href="{{ route('reports.attendance', request()->query() + ['format' => 'print']) }}" target="_blank"><x-secondary-button>PDF / Cetak</x-secondary-button></a>
            <a href="{{ route('reports.attendance', request()->query() + ['format' => 'csv']) }}"><x-secondary-button>CSV</x-secondary-button></a>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <form method="GET" action="{{ route('reports.attendance') }}" class="flex flex-wrap items-end gap-2 mb-4">
            <input type="hidden" name="period" value="">
            <div>
                <x-input-label for="month" value="Bulan" />
                <x-text-input id="month" name="month" type="month" class="mt-1" :value="request('month', now()->format('Y-m'))" />
            </div>
            <div>
                <x-input-label for="from" value="Dari" />
                <x-text-input id="from" name="from" type="date" class="mt-1" :value="request('from')" />
            </div>
            <div>
                <x-input-label for="to" value="Sampai" />
                <x-text-input id="to" name="to" type="date" class="mt-1" :value="request('to')" />
            </div>
            <div>
                <x-input-label for="department_id" value="Departemen" />
                <x-select-input id="department_id" name="department_id" class="mt-1">
                    <option value="">Semua</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </x-select-input>
            </div>
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
            <x-empty-state title="Tidak ada data absensi" />
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
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($records as $record)
                            <tr>
                                <td class="py-3 px-2 text-slate-700">{{ $record->work_date->format('d/m/Y') }}</td>
                                <td class="py-3 pr-4">
                                    <span class="block font-medium text-slate-800">{{ $record->employee->full_name ?? '—' }}</span>
                                    <span class="block text-xs text-slate-500">{{ $record->employee->department->name ?? '—' }}</span>
                                </td>
                                <td class="py-3 pr-4 text-slate-600">{{ $record->check_in_at?->timezone('Asia/Jakarta')->format('H:i') ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $record->check_out_at?->timezone('Asia/Jakarta')->format('H:i') ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $record->late_minutes > 0 ? $record->late_minutes.' mnt' : '—' }}</td>
                                <td class="py-3 pr-4"><x-status-badge :status="$record->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $records->links() }}</div>
        @endif
    </x-card>
</x-app-layout>

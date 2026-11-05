<x-app-layout>
    <x-slot name="header">Rekap Lembur</x-slot>

    <x-breadcrumbs :items="[
        ['label' => 'Laporan', 'url' => route('reports.index')],
        ['label' => 'Lembur'],
    ]" />

    <x-page-header title="Rekap Lembur" subtitle="Total lembur disetujui dan rincian per karyawan.">
        <x-slot:actions>
            <a href="{{ route('reports.overtime', request()->query() + ['format' => 'xlsx']) }}"><x-secondary-button>Excel</x-secondary-button></a>
            <a href="{{ route('reports.overtime', request()->query() + ['format' => 'print']) }}" target="_blank"><x-secondary-button>PDF / Cetak</x-secondary-button></a>
            <a href="{{ route('reports.overtime', request()->query() + ['format' => 'csv']) }}"><x-secondary-button>CSV</x-secondary-button></a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <x-stat-card label="Total Lembur Disetujui" :value="intdiv($approvedMinutes, 60).'j '.($approvedMinutes % 60).'m'" accent="emerald" />
        <x-stat-card label="Jumlah Pengajuan (halaman ini)" :value="$overtimes->total()" accent="blue" />
    </div>

    <x-card>
        <form method="GET" action="{{ route('reports.overtime') }}" class="flex flex-wrap items-end gap-2 mb-4">
            <input type="hidden" name="period" value="{{ $period }}">
            @if($period === 'daily')
                <div>
                    <x-input-label for="date" value="Tanggal" />
                    <x-text-input id="date" name="date" type="date" class="mt-1" :value="request('date', tz_today()->toDateString())" />
                </div>
            @elseif($period === 'range')
                <div>
                    <x-input-label for="from" value="Dari" />
                    <x-text-input id="from" name="from" type="date" class="mt-1" :value="request('from')" />
                </div>
                <div>
                    <x-input-label for="to" value="Sampai" />
                    <x-text-input id="to" name="to" type="date" class="mt-1" :value="request('to')" />
                </div>
            @else
                <div>
                    <x-input-label for="month" value="Bulan" />
                    <x-text-input id="month" name="month" type="month" class="mt-1" :value="request('month', tz_today()->format('Y-m'))" />
                </div>
            @endif
            <div>
                <x-input-label for="view" value="Periode" />
                <x-select-input id="view" name="period" class="mt-1" onchange="this.form.submit()">
                    <option value="monthly" @selected($period === 'monthly')>Bulanan</option>
                    <option value="daily" @selected($period === 'daily')>Harian</option>
                    <option value="range" @selected($period === 'range')>Rentang</option>
                </x-select-input>
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
                    @foreach(['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <x-secondary-button type="submit">Terapkan</x-secondary-button>
        </form>

        @if($overtimes->isEmpty())
            <x-empty-state title="Tidak ada data lembur" />
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                            <th class="py-2 px-2 font-medium">Tanggal</th>
                            <th class="py-2 pr-4 font-medium">Karyawan</th>
                            <th class="py-2 pr-4 font-medium">Departemen</th>
                            <th class="py-2 pr-4 font-medium">Waktu</th>
                            <th class="py-2 pr-4 font-medium">Durasi</th>
                            <th class="py-2 pr-4 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($overtimes as $overtime)
                            <tr>
                                <td class="py-3 px-2 text-slate-700">{{ $overtime->date->format('d/m/Y') }}</td>
                                <td class="py-3 pr-4 font-medium text-slate-800">{{ $overtime->employee->full_name ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $overtime->employee->department->name ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $overtime->startLabel() }} - {{ $overtime->endLabel() }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ intdiv($overtime->duration_minutes, 60) }}j {{ $overtime->duration_minutes % 60 }}m</td>
                                <td class="py-3 pr-4"><x-status-badge :status="$overtime->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $overtimes->links() }}</div>
        @endif
    </x-card>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">Laporan Karyawan</x-slot>

    <x-page-header title="Laporan Data Karyawan" subtitle="Filter dan ekspor CSV.">
        <x-slot:actions>
            <a href="{{ route('reports.employees', request()->query() + ['format' => 'xlsx']) }}"><x-secondary-button>Excel</x-secondary-button></a>
            <a href="{{ route('reports.employees', request()->query() + ['format' => 'print']) }}" target="_blank"><x-secondary-button>PDF / Cetak</x-secondary-button></a>
            <a href="{{ route('reports.employees', request()->query() + ['format' => 'csv']) }}"><x-secondary-button>CSV</x-secondary-button></a>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <form method="GET" action="{{ route('reports.employees') }}" class="flex flex-wrap gap-2 mb-4">
            <x-text-input name="search" :value="request('search')" placeholder="Cari nama atau ID..." class="flex-1 min-w-[200px]" />
            <x-select-input name="department_id" onchange="this.form.submit()">
                <option value="">Semua departemen</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                @endforeach
            </x-select-input>
            <x-select-input name="status" onchange="this.form.submit()">
                <option value="">Semua status</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                <option value="resigned" @selected(request('status') === 'resigned')>Resign</option>
            </x-select-input>
            <x-secondary-button type="submit">Terapkan</x-secondary-button>
        </form>

        @if($employees->isEmpty())
            <x-empty-state title="Tidak ada data" />
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                            <th class="py-2 px-2 font-medium">ID</th>
                            <th class="py-2 pr-4 font-medium">Nama</th>
                            <th class="py-2 pr-4 font-medium">Departemen / Jabatan</th>
                            <th class="py-2 pr-4 font-medium">Tgl Masuk</th>
                            <th class="py-2 pr-4 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($employees as $employee)
                            <tr>
                                <td class="py-3 px-2 font-mono text-xs text-slate-600">{{ $employee->employee_id }}</td>
                                <td class="py-3 pr-4 font-medium text-slate-900">{{ $employee->full_name }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $employee->department->name ?? '—' }} / {{ $employee->position->name ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $employee->join_date?->format('d/m/Y') }}</td>
                                <td class="py-3 pr-4"><x-status-badge :status="$employee->employment_status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $employees->links() }}</div>
        @endif
    </x-card>
</x-app-layout>

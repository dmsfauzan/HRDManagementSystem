<x-app-layout>
    <x-slot name="header">Rekap Cuti</x-slot>

    <x-page-header title="Rekap Cuti" subtitle="Daftar pengajuan cuti dan status persetujuan.">
        <x-slot:actions>
            <a href="{{ route('reports.leaves', request()->query() + ['export' => 'csv']) }}">
                <x-secondary-button>Unduh CSV</x-secondary-button>
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <form method="GET" action="{{ route('reports.leaves') }}" class="flex flex-wrap gap-2 mb-4">
            <x-select-input name="status" onchange="this.form.submit()">
                <option value="">Semua status</option>
                @foreach(['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </x-select-input>
            <x-select-input name="leave_type_id" onchange="this.form.submit()">
                <option value="">Semua jenis</option>
                @foreach($leaveTypes as $type)
                    <option value="{{ $type->id }}" @selected((string) request('leave_type_id') === (string) $type->id)>{{ $type->name }}</option>
                @endforeach
            </x-select-input>
            <x-select-input name="department_id" onchange="this.form.submit()">
                <option value="">Semua departemen</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                @endforeach
            </x-select-input>
            <x-text-input name="from" type="date" :value="request('from')" />
            <x-text-input name="to" type="date" :value="request('to')" />
            <x-secondary-button type="submit">Terapkan</x-secondary-button>
        </form>

        @if($requests->isEmpty())
            <x-empty-state title="Tidak ada data cuti" />
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                            <th class="py-2 px-2 font-medium">Karyawan</th>
                            <th class="py-2 pr-4 font-medium">Jenis</th>
                            <th class="py-2 pr-4 font-medium">Periode</th>
                            <th class="py-2 pr-4 font-medium">Hari</th>
                            <th class="py-2 pr-4 font-medium">Status</th>
                            <th class="py-2 pr-4 font-medium">Keputusan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($requests as $leave)
                            <tr>
                                <td class="py-3 px-2 font-medium text-slate-800">{{ $leave->employee->full_name ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-700">{{ $leave->leaveType->name ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $leave->start_date->format('d/m/Y') }} &ndash; {{ $leave->end_date->format('d/m/Y') }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ rtrim(rtrim(number_format($leave->total_days, 2, ',', '.'), '0'), ',') }}</td>
                                <td class="py-3 pr-4"><x-status-badge :status="$leave->status" /></td>
                                <td class="py-3 pr-4 text-slate-600 text-xs">{{ $leave->approver->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $requests->links() }}</div>
        @endif
    </x-card>
</x-app-layout>

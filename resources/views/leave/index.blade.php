<x-app-layout>
    <x-slot name="header">Pengajuan Cuti</x-slot>

    <x-page-header title="Pengajuan Cuti" subtitle="Riwayat dan status pengajuan cuti.">
        <x-slot:actions>
            @can('create', \App\Models\LeaveRequest::class)
                <a href="{{ route('leave.create') }}"><x-primary-button>Ajukan Cuti</x-primary-button></a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <form method="GET" action="{{ route('leave.index') }}" class="flex flex-wrap gap-2 mb-4">
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
            @if(auth()->user()->hasRole('manager') && auth()->user()->employee)
                <x-select-input name="scope" onchange="this.form.submit()">
                    <option value="team" @selected(request('scope', 'team') === 'team')>Tim Saya</option>
                    <option value="own" @selected(request('scope') === 'own')>Pengajuan Saya</option>
                </x-select-input>
            @endif
            <x-secondary-button type="submit">Filter</x-secondary-button>
        </form>

        @if($requests->isEmpty())
            <x-empty-state title="Belum ada pengajuan cuti" />
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
                            <th class="py-2 pr-4 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($requests as $leave)
                            <tr class="hover:bg-slate-50/60">
                                <td class="py-3 px-2">
                                    <span class="block font-medium text-slate-800">{{ $leave->employee->full_name ?? '—' }}</span>
                                    <span class="block text-xs text-slate-500">{{ $leave->employee->department->name ?? '—' }}</span>
                                </td>
                                <td class="py-3 pr-4 text-slate-700">{{ $leave->leaveType->name ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $leave->start_date->translatedFormat('d M Y') }} &ndash; {{ $leave->end_date->translatedFormat('d M Y') }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ rtrim(rtrim(number_format($leave->total_days, 2, ',', '.'), '0'), ',') }}</td>
                                <td class="py-3 pr-4"><x-status-badge :status="$leave->status" /></td>
                                <td class="py-3 pr-4 text-right">
                                    <a href="{{ route('leave.show', $leave) }}" class="text-sm text-slate-600 hover:text-slate-900">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $requests->links() }}</div>
        @endif
    </x-card>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">Persetujuan Cuti</x-slot>

    <x-page-header title="Persetujuan Cuti" subtitle="Pengajuan yang menunggu keputusan Anda." />

    <x-card>
        @if($requests->isEmpty())
            <x-empty-state title="Tidak ada pengajuan menunggu" />
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                            <th class="py-2 px-2 font-medium">Karyawan</th>
                            <th class="py-2 pr-4 font-medium">Jenis</th>
                            <th class="py-2 pr-4 font-medium">Periode</th>
                            <th class="py-2 pr-4 font-medium">Hari</th>
                            <th class="py-2 pr-4 font-medium">Sisa Saldo</th>
                            <th class="py-2 pr-4 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($requests as $leave)
                            <tr class="hover:bg-slate-50/60">
                                <td class="py-3 px-2">
                                    <span class="block font-medium text-slate-800">{{ $leave->employee->full_name }}</span>
                                    <span class="block text-xs text-slate-500">{{ $leave->employee->department->name ?? '—' }}</span>
                                </td>
                                <td class="py-3 pr-4 text-slate-700">{{ $leave->leaveType->name ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $leave->start_date->translatedFormat('d M Y') }} &ndash; {{ $leave->end_date->translatedFormat('d M Y') }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ rtrim(rtrim(number_format($leave->total_days, 2, ',', '.'), '0'), ',') }}</td>
                                <td class="py-3 pr-4 text-slate-600">
                                    @php($bal = $leave->employee->leaveBalances->firstWhere('leave_type_id', $leave->leave_type_id))
                                    @if($bal !== null)
                                        {{ rtrim(rtrim(number_format($bal->balance, 2, ',', '.'), '0'), ',') }} hari
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="py-3 pr-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('leave.show', $leave) }}" class="text-sm text-slate-600 hover:text-slate-900">Detail</a>
                                        <form method="POST" action="{{ route('leave.decide', $leave) }}">@csrf @method('PATCH')
                                            <input type="hidden" name="action" value="approved">
                                            <button type="submit" class="text-xs px-2.5 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700">Setujui</button>
                                        </form>
                                        <a href="{{ route('leave.show', $leave) }}" class="text-xs px-2.5 py-1 rounded bg-red-600 text-white hover:bg-red-700">Tolak</a>
                                    </div>
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

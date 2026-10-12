<x-app-layout>
    <x-slot name="header">Lembur</x-slot>

    <x-page-header title="Lembur" subtitle="Pengajuan dan persetujuan lembur.">
        <x-slot:actions>
            @can('create', \App\Models\Overtime::class)
                <a href="{{ route('overtime.create') }}"><x-primary-button>Ajukan Lembur</x-primary-button></a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <form method="GET" action="{{ route('overtime.index') }}" class="mb-4">
            <x-select-input name="status" onchange="this.form.submit()">
                <option value="">Semua status</option>
                @foreach(['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </x-select-input>
        </form>

        @if($overtimes->isEmpty())
            <x-empty-state title="Belum ada pengajuan lembur" />
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                            <th class="py-2 px-2 font-medium">Karyawan</th>
                            <th class="py-2 pr-4 font-medium">Tanggal</th>
                            <th class="py-2 pr-4 font-medium">Waktu</th>
                            <th class="py-2 pr-4 font-medium">Durasi</th>
                            <th class="py-2 pr-4 font-medium">Status</th>
                            <th class="py-2 pr-4 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($overtimes as $overtime)
                            <tr class="hover:bg-slate-50/60">
                                <td class="py-3 px-2">
                                    <span class="block font-medium text-slate-800">{{ $overtime->employee->full_name ?? '—' }}</span>
                                    <span class="block text-xs text-slate-500">{{ $overtime->employee->department->name ?? '—' }}</span>
                                </td>
                                <td class="py-3 pr-4 text-slate-700">{{ $overtime->date->translatedFormat('d M Y') }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $overtime->startLabel() }} - {{ $overtime->endLabel() }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ intdiv($overtime->duration_minutes, 60) }}j {{ $overtime->duration_minutes % 60 }}m</td>
                                <td class="py-3 pr-4"><x-status-badge :status="$overtime->status" /></td>
                                <td class="py-3 pr-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @can('decide', $overtime)
                                            @if($overtime->status === 'pending')
                                                <form method="POST" action="{{ route('overtime.decide', $overtime) }}">@csrf @method('PATCH')
                                                    <input type="hidden" name="action" value="approved">
                                                    <button type="submit" class="text-xs px-2.5 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700">Setujui</button>
                                                </form>
                                                <form method="POST" action="{{ route('overtime.decide', $overtime) }}">@csrf @method('PATCH')
                                                    <input type="hidden" name="action" value="rejected">
                                                    <input type="hidden" name="decision_note" value="Ditolak oleh approver">
                                                    <button type="submit" class="text-xs px-2.5 py-1 rounded bg-red-600 text-white hover:bg-red-700">Tolak</button>
                                                </form>
                                            @endif
                                        @endcan
                                        @can('cancel', $overtime)
                                            @if(in_array($overtime->status, ['pending', 'approved']))
                                                <form method="POST" action="{{ route('overtime.cancel', $overtime) }}" onsubmit="return confirm('Batalkan pengajuan lembur ini?')">@csrf @method('PATCH')
                                                    <button type="submit" class="text-xs text-slate-600 hover:text-slate-900">Batalkan</button>
                                                </form>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $overtimes->links() }}</div>
        @endif
    </x-card>
</x-app-layout>

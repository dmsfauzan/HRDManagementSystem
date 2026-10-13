<x-app-layout>
    <x-slot name="header">Tukar Shift</x-slot>

    <x-page-header title="Tukar Shift" subtitle="Pengajuan dan persetujuan pertukaran jadwal antar karyawan.">
        <x-slot:actions>
            @if(auth()->user()->employee)
                <a href="{{ route('shift-swaps.create') }}"><x-primary-button>Ajukan Tukar</x-primary-button></a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-card>
        @if($swaps->isEmpty())
            <x-empty-state title="Belum ada pengajuan tukar shift" />
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                            <th class="py-2 px-2 font-medium">Pemohon</th>
                            <th class="py-2 pr-4 font-medium">Tukar Dengan</th>
                            <th class="py-2 pr-4 font-medium">Tanggal</th>
                            <th class="py-2 pr-4 font-medium">Alasan</th>
                            <th class="py-2 pr-4 font-medium">Status</th>
                            <th class="py-2 pr-4 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($swaps as $swap)
                            <tr>
                                <td class="py-3 px-2 font-medium text-slate-800">{{ $swap->requester->full_name ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-700">{{ $swap->target->full_name ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $swap->date?->translatedFormat('d M Y') }}</td>
                                <td class="py-3 pr-4 text-slate-600 max-w-[220px] truncate">{{ $swap->reason }}</td>
                                <td class="py-3 pr-4"><x-status-badge :status="$swap->status" /></td>
                                <td class="py-3 pr-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($swap->status === 'pending' && (auth()->user()->can('attendance.manage') || (auth()->user()->hasRole('manager') && auth()->user()->employee && $swap->requester?->manager_id === auth()->user()->employee->id)))
                                            <form method="POST" action="{{ route('shift-swaps.decide', $swap) }}">@csrf @method('PATCH')
                                                <input type="hidden" name="action" value="approved">
                                                <button type="submit" class="text-xs px-2.5 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700">Setujui</button>
                                            </form>
                                            <form method="POST" action="{{ route('shift-swaps.decide', $swap) }}">@csrf @method('PATCH')
                                                <input type="hidden" name="action" value="rejected">
                                                <input type="hidden" name="decision_note" value="Ditolak">
                                                <button type="submit" class="text-xs px-2.5 py-1 rounded bg-red-600 text-white hover:bg-red-700">Tolak</button>
                                            </form>
                                        @endif
                                        @if($swap->status === 'pending' && auth()->user()->employee && auth()->user()->employee->id === $swap->requester_employee_id)
                                            <form method="POST" action="{{ route('shift-swaps.cancel', $swap) }}">@csrf @method('PATCH')
                                                <button type="submit" class="text-xs text-slate-600 hover:text-slate-900">Batalkan</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $swaps->links() }}</div>
        @endif
    </x-card>
</x-app-layout>

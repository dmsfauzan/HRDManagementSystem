<x-app-layout>
    <x-slot name="header">Koreksi Absensi</x-slot>

    <x-page-header title="Koreksi Absensi" subtitle="Ajukan perbaikan data absensi dan kelola persetujuan.">
        <x-slot:actions>
            <a href="{{ route('attendance.corrections.create') }}"><x-primary-button>Ajukan Koreksi</x-primary-button></a>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <form method="GET" action="{{ route('attendance.corrections.index') }}" class="flex gap-2 mb-4">
            <x-select-input name="status" onchange="this.form.submit()">
                <option value="">Semua status</option>
                @foreach(['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </x-select-input>
            <x-secondary-button type="submit">Filter</x-secondary-button>
        </form>

        @if($corrections->isEmpty())
            <x-empty-state title="Belum ada pengajuan koreksi" />
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                            <th class="py-2 px-2 font-medium">Karyawan</th>
                            <th class="py-2 pr-4 font-medium">Tanggal</th>
                            <th class="py-2 pr-4 font-medium">Permintaan</th>
                            <th class="py-2 pr-4 font-medium">Alasan</th>
                            <th class="py-2 pr-4 font-medium">Status</th>
                            <th class="py-2 pr-4 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($corrections as $correction)
                            <tr class="hover:bg-slate-50/60">
                                <td class="py-3 px-2">
                                    <span class="block font-medium text-slate-800">{{ $correction->employee->full_name ?? '—' }}</span>
                                    <span class="block text-xs text-slate-500">{{ $correction->employee->employee_id ?? '—' }}</span>
                                </td>
                                <td class="py-3 pr-4 text-slate-700">{{ $correction->work_date->translatedFormat('d M Y') }}</td>
                                <td class="py-3 pr-4 text-slate-600 text-xs">
                                    @if($correction->requested_check_in_at) Masuk {{ $correction->requested_check_in_at->timezone(tz())->format('H:i') }}<br>@endif
                                    @if($correction->requested_check_out_at) Keluar {{ $correction->requested_check_out_at->timezone(tz())->format('H:i') }}@endif
                                </td>
                                <td class="py-3 pr-4 text-slate-600 max-w-[260px] truncate">{{ $correction->reason }}</td>
                                <td class="py-3 pr-4"><x-status-badge :status="$correction->status" /></td>
                                <td class="py-3 pr-4 text-right">
                                    @can('decide', $correction)
                                        @if($correction->status === 'pending')
                                            <div class="flex items-center gap-1 justify-end">
                                                <form method="POST" action="{{ route('attendance.corrections.decide', $correction) }}">@csrf @method('PATCH')
                                                    <input type="hidden" name="action" value="approved">
                                                    <button type="submit" class="text-xs px-2.5 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700">Setujui</button>
                                                </form>
                                                <form method="POST" action="{{ route('attendance.corrections.decide', $correction) }}">@csrf @method('PATCH')
                                                    <input type="hidden" name="action" value="rejected">
                                                    <input type="hidden" name="decision_note" value="Ditolak oleh atasan">
                                                    <button type="submit" class="text-xs px-2.5 py-1 rounded bg-red-600 text-white hover:bg-red-700">Tolak</button>
                                                </form>
                                            </div>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $corrections->links() }}</div>
        @endif
    </x-card>
</x-app-layout>

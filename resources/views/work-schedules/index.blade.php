<x-app-layout>
    <x-slot name="header">Jadwal Kerja</x-slot>

    <x-page-header title="Jadwal Kerja" subtitle="Kelola jam kerja, toleransi keterlambatan, dan hari kerja.">
        <x-slot:actions>
            <a href="{{ route('work-schedules.create') }}"><x-primary-button>Tambah Jadwal</x-primary-button></a>
        </x-slot:actions>
    </x-page-header>

    @php($dayNames = [1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 7 => 'Min'])

    <x-card>
        @if($schedules->isEmpty())
            <x-empty-state title="Belum ada jadwal kerja" />
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                            <th class="py-2 px-2 font-medium">Kode</th>
                            <th class="py-2 pr-4 font-medium">Nama</th>
                            <th class="py-2 pr-4 font-medium">Jam</th>
                            <th class="py-2 pr-4 font-medium">Hari Kerja</th>
                            <th class="py-2 pr-4 font-medium">Toleransi</th>
                            <th class="py-2 pr-4 font-medium">Karyawan</th>
                            <th class="py-2 pr-4 font-medium">Status</th>
                            <th class="py-2 pr-4 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($schedules as $schedule)
                            <tr class="hover:bg-slate-50/60">
                                <td class="py-3 px-2 font-mono text-xs text-slate-600">{{ $schedule->code }}</td>
                                <td class="py-3 pr-4 font-medium text-slate-800">{{ $schedule->name }}
                                    @if($schedule->crosses_midnight)<span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700">Lintas hari</span>@endif
                                </td>
                                <td class="py-3 pr-4 text-slate-600">{{ substr($schedule->check_in_time, 0, 5) }} - {{ substr($schedule->check_out_time, 0, 5) }}</td>
                                <td class="py-3 pr-4 text-slate-600">
                                    @foreach($schedule->work_days ?? [] as $d)
                                        <span class="inline-block text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 mr-0.5">{{ $dayNames[$d] ?? $d }}</span>
                                    @endforeach
                                </td>
                                <td class="py-3 pr-4 text-slate-600">{{ $schedule->late_tolerance_minutes }} menit</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $schedule->employees_count }}</td>
                                <td class="py-3 pr-4"><x-status-badge :status="$schedule->is_active ? 'active' : 'inactive'" /></td>
                                <td class="py-3 pr-4 text-right space-x-3">
                                    <a href="{{ route('work-schedules.edit', $schedule) }}" class="text-sm text-slate-600 hover:text-slate-900">Ubah</a>
                                    <form method="POST" action="{{ route('work-schedules.destroy', $schedule) }}" onsubmit="return confirm('Hapus jadwal ini?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $schedules->links() }}</div>
        @endif
    </x-card>
</x-app-layout>

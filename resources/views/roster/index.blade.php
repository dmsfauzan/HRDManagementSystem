<x-app-layout>
    <x-slot name="header">Roster Shift</x-slot>

    <x-page-header :title="'Roster Mingguan — ' . $weekStart->translatedFormat('d M Y')" subtitle="Tetapkan jadwal kerja per karyawan untuk minggu ini.">
        <x-slot:actions>
            <div class="flex items-center gap-2">
                <a href="{{ route('roster.index', ['week' => $weekStart->copy()->subWeek()->toDateString()]) }}"><x-secondary-button type="button">&larr; Minggu lalu</x-secondary-button></a>
                <a href="{{ route('roster.index') }}"><x-secondary-button type="button">Minggu ini</x-secondary-button></a>
                <a href="{{ route('roster.index', ['week' => $weekStart->copy()->addWeek()->toDateString()]) }}"><x-secondary-button type="button">Minggu depan &rarr;</x-secondary-button></a>
            </div>
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ route('roster.store') }}">
        @csrf
        <input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">
        <x-card>
            @if($employees->isEmpty())
                <x-empty-state title="Belum ada karyawan aktif" />
            @else
                <div class="overflow-x-auto -mx-5">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                                <th class="py-2 px-2 font-medium">Karyawan</th>
                                <th class="py-2 pr-4 font-medium">Jadwal Default</th>
                                <th class="py-2 pr-4 font-medium">Jadwal Minggu Ini</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach($employees as $employee)
                                @php($assigned = $assignments[$employee->id] ?? null)
                                <tr>
                                    <td class="py-2 px-2">
                                        <span class="block font-medium text-slate-800">{{ $employee->full_name }}</span>
                                        <span class="block text-xs text-slate-500">{{ $employee->department->name ?? '—' }}</span>
                                    </td>
                                    <td class="py-2 pr-4 text-slate-600">{{ $employee->workSchedule->name ?? '—' }}</td>
                                    <td class="py-2 pr-4">
                                        <x-select-input name="assignments[{{ $employee->id }}]" class="block w-full">
                                            <option value="">— Ikuti default —</option>
                                            @foreach($schedules as $schedule)
                                                <option value="{{ $schedule->id }}" @selected((int) ($assigned->work_schedule_id ?? 0) === $schedule->id)>{{ $schedule->name }}</option>
                                            @endforeach
                                        </x-select-input>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    <x-primary-button>Simpan Roster</x-primary-button>
                </div>
            @endif
        </x-card>
    </form>
</x-app-layout>

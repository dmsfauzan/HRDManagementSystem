<x-app-layout>
    <x-slot name="header">Kalender Cuti</x-slot>

    <x-page-header :title="'Kalender Cuti — ' . $month->translatedFormat('F Y')" subtitle="Cuti disetujui dan menunggu (hari kerja).">
        <x-slot:actions>
            <div class="flex items-center gap-2">
                <a href="{{ route('leave.calendar', ['month' => $prevMonth]) }}"><x-secondary-button type="button">&larr;</x-secondary-button></a>
                <a href="{{ route('leave.calendar') }}"><x-secondary-button type="button">Bulan Ini</x-secondary-button></a>
                <a href="{{ route('leave.calendar', ['month' => $nextMonth]) }}"><x-secondary-button type="button">&rarr;</x-secondary-button></a>
            </div>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <div class="grid grid-cols-7 mb-2 text-center text-xs font-medium uppercase tracking-wide text-slate-400">
            @foreach(['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $d)
                <div class="py-1">{{ $d }}</div>
            @endforeach
        </div>
        <div class="border border-slate-100 rounded-lg overflow-hidden">
            @foreach($weeks as $week)
                <div class="grid grid-cols-7 {{ ! $loop->last ? 'border-b border-slate-100' : '' }}">
                    @foreach($week as $day)
                        <div class="min-h-[92px] p-1.5 border-r border-slate-100 last:border-r-0 {{ $day->month !== $month->month ? 'bg-slate-50/60' : '' }} {{ $day->isWeekend() ? 'bg-slate-50/40' : '' }}">
                            <div class="text-xs {{ $day->isToday() ? 'font-bold text-emerald-700' : 'text-slate-500' }}">{{ $day->day }}</div>
                            <div class="mt-1 space-y-0.5">
                                @foreach(($byDay[$day->toDateString()] ?? []) as $leave)
                                    <div class="text-[10px] leading-tight px-1 py-0.5 rounded truncate {{ $leave->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}"
                                        title="{{ $leave->employee->full_name ?? '' }} — {{ $leave->leaveType->name ?? '' }} ({{ $leave->status }})">
                                        {{ \Illuminate\Support\Str::limit($leave->employee->full_name ?? '-', 14) }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
        <div class="flex items-center gap-4 mt-4 text-xs text-slate-500">
            <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-emerald-100 border border-emerald-300"></span> Disetujui</span>
            <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-amber-100 border border-amber-300"></span> Menunggu</span>
        </div>
    </x-card>
</x-app-layout>

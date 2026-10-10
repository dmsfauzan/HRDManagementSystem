<x-app-layout>
    <x-slot name="header">Audit Log</x-slot>

    <x-page-header title="Audit Log" subtitle="Jejak aktivitas sensitif. Akses terbatas Super Admin/HRD." />

    <x-card>
        <form method="GET" action="{{ route('audit.index') }}" class="flex flex-wrap gap-2 mb-4">
            <x-text-input name="action" :value="request('action')" placeholder="Cari aksi (leave.decided, employee.created, report.exported ...)" class="flex-1 min-w-[220px]" />
            <x-text-input name="from" type="date" :value="request('from')" />
            <x-text-input name="to" type="date" :value="request('to')" />
            <x-secondary-button type="submit">Terapkan</x-secondary-button>
        </form>

        @if($logs->isEmpty())
            <x-empty-state title="Belum ada aktivitas tercatat" />
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                            <th class="py-2 px-2 font-medium">Waktu</th>
                            <th class="py-2 pr-4 font-medium">Pelaku</th>
                            <th class="py-2 pr-4 font-medium">Aksi</th>
                            <th class="py-2 pr-4 font-medium">Target</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($logs as $log)
                            <tr>
                                <td class="py-3 px-2 text-slate-600 text-xs">{{ $log->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td>
                                <td class="py-3 pr-4 text-slate-800 text-xs">{{ $log->user->name ?? 'Sistem' }}</td>
                                <td class="py-3 pr-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-slate-100 text-slate-700">{{ $log->action }}</span>
                                </td>
                                <td class="py-3 pr-4 text-slate-600 text-xs">
                                    {{ $log->auditable_type ? class_basename($log->auditable_type) : '—' }}
                                    @if($log->auditable_id) #{{ $log->auditable_id }} @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $logs->links() }}</div>
        @endif
    </x-card>

    @if($logs->isNotEmpty())
        <x-card title="Detail Log Terpilih (terbaru)">
            @php($latest = $logs->first())
            <dl class="text-sm space-y-2">
                <div><dt class="text-slate-500">Aksi</dt><dd class="font-mono text-xs">{{ $latest->action }}</dd></div>
                @if($latest->old_values)<div><dt class="text-slate-500">Before</dt><dd class="font-mono text-xs text-slate-600 break-all">{{ json_encode($latest->old_values, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</dd></div>@endif
                @if($latest->new_values)<div><dt class="text-slate-500">After</dt><dd class="font-mono text-xs text-slate-600 break-all">{{ json_encode($latest->new_values, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</dd></div>@endif
            </dl>
        </x-card>
    @endif
</x-app-layout>

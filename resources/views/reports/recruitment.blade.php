<x-app-layout>
    <x-slot name="header">Laporan Rekrutmen</x-slot>

    <x-page-header title="Laporan Rekrutmen" subtitle="Funnel, time-to-hire, dan sumber pelamar.">
        <x-slot:actions>
            <a href="{{ route('reports.recruitment', request()->query() + ['format' => 'xlsx']) }}"><x-secondary-button>Excel</x-secondary-button></a>
            <a href="{{ route('reports.recruitment', request()->query() + ['format' => 'print']) }}" target="_blank"><x-secondary-button>PDF / Cetak</x-secondary-button></a>
            <a href="{{ route('reports.recruitment', request()->query() + ['format' => 'csv']) }}"><x-secondary-button>CSV</x-secondary-button></a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-stat-card label="Total Pelamar" :value="$total" accent="blue" />
        <x-stat-card label="Diterima (Hired)" :value="$hired" accent="emerald" />
        <x-stat-card label="Rata-rata Time-to-Hire" :value="$avgTimeToHire ? round($avgTimeToHire, 1).' hari' : '—'" accent="amber" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <x-card title="Funnel Tahapan">
            @php($maxFunnel = max(1, $funnel->max() ?? 1))
            @foreach(['applied' => 'Applied', 'screening' => 'Screening', 'interview' => 'Interview', 'offer' => 'Offer', 'hired' => 'Hired', 'rejected' => 'Rejected', 'withdrawn' => 'Withdrawn'] as $key => $label)
                <div class="mb-2 last:mb-0">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-slate-700">{{ $label }}</span>
                        <span class="font-medium text-slate-900">{{ (int) ($funnel[$key] ?? 0) }}</span>
                    </div>
                    <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ round(((int) ($funnel[$key] ?? 0)) / $maxFunnel * 100) }}%"></div>
                    </div>
                </div>
            @endforeach
        </x-card>

        <x-card title="Sumber Pelamar">
            @php($maxSource = max(1, $sources->max() ?? 1))
            @forelse($sources as $source => $count)
                <div class="mb-2 last:mb-0">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-slate-700">{{ \Illuminate\Support\Str::headline($source) }}</span>
                        <span class="font-medium text-slate-900">{{ $count }}</span>
                    </div>
                    <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full bg-blue-500" style="width: {{ round($count / $maxSource * 100) }}%"></div>
                    </div>
                </div>
            @empty
                <x-empty-state title="Belum ada data sumber" />
            @endforelse
        </x-card>
    </div>

    <x-card>
        <form method="GET" action="{{ route('reports.recruitment') }}" class="flex flex-wrap gap-2 mb-4">
            <x-select-input name="posting_id" onchange="this.form.submit()">
                <option value="">Semua lowongan</option>
                @foreach($postings as $posting)
                    <option value="{{ $posting->id }}" @selected((string) request('posting_id') === (string) $posting->id)>{{ $posting->title }}</option>
                @endforeach
            </x-select-input>
            <x-select-input name="status" onchange="this.form.submit()">
                <option value="">Semua status</option>
                @foreach(\App\Models\JobApplication::STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ \Illuminate\Support\Str::headline($status) }}</option>
                @endforeach
            </x-select-input>
            <x-text-input name="from" type="date" :value="request('from')" />
            <x-text-input name="to" type="date" :value="request('to')" />
            <x-secondary-button type="submit">Terapkan</x-secondary-button>
        </form>

        @if($applications->isEmpty())
            <x-empty-state title="Tidak ada data pelamar" />
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                            <th class="py-2 px-2 font-medium">Kandidat</th>
                            <th class="py-2 pr-4 font-medium">Posisi</th>
                            <th class="py-2 pr-4 font-medium">Sumber</th>
                            <th class="py-2 pr-4 font-medium">Status</th>
                            <th class="py-2 pr-4 font-medium">Tgl Lamar</th>
                            <th class="py-2 pr-4 font-medium">Hari ke-hire</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($applications as $application)
                            <tr>
                                <td class="py-3 px-2 font-medium text-slate-800">{{ $application->candidate_name }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $application->jobPosting->title ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-600 text-xs">{{ $application->source ?? '—' }}</td>
                                <td class="py-3 pr-4"><x-status-badge :status="$application->status" /></td>
                                <td class="py-3 pr-4 text-slate-600 text-xs">{{ $application->created_at?->format('d/m/Y') }}</td>
                                <td class="py-3 pr-4 text-slate-600 text-xs">{{ $application->hired_at ? $application->created_at->diffInDays($application->hired_at).' hari' : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $applications->links() }}</div>
        @endif
    </x-card>
</x-app-layout>

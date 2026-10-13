<x-app-layout>
    <x-slot name="header">Pipeline Rekrutmen</x-slot>

    <x-page-header title="Pipeline Rekrutmen" subtitle="Papan status kandidat.">
        <x-slot:actions>
            <a href="{{ route('recruitment.applications.index') }}"><x-secondary-button>Daftar</x-secondary-button></a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-6 gap-3">
        @foreach($byStatus as $status => $items)
            <div class="bg-slate-100 rounded-xl p-3">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-600">{{ \Illuminate\Support\Str::headline($status) }}</span>
                    <span class="text-xs px-1.5 py-0.5 rounded bg-white text-slate-600">{{ $items->count() }}</span>
                </div>
                <div class="space-y-2">
                    @foreach($items as $application)
                        <a href="{{ route('recruitment.applications.show', $application) }}" class="block bg-white rounded-lg border border-slate-200 p-3 hover:border-emerald-300">
                            <span class="block text-sm font-medium text-slate-800 truncate">{{ $application->candidate_name }}</span>
                            <span class="block text-xs text-slate-500 truncate">{{ $application->jobPosting->title ?? '—' }}</span>
                        </a>
                    @endforeach
                    @if($items->isEmpty())
                        <p class="text-xs text-slate-400 text-center py-2">—</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>

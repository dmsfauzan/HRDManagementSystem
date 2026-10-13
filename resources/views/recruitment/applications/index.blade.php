<x-app-layout>
    <x-slot name="header">Pelamar</x-slot>

    <x-page-header title="Pelamar" subtitle="Daftar kandidat rekrutmen.">
        <x-slot:actions>
            <a href="{{ route('recruitment.applications.kanban') }}"><x-secondary-button>Pipeline</x-secondary-button></a>
            <a href="{{ route('recruitment.postings.index') }}"><x-secondary-button>Lowongan</x-secondary-button></a>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <form method="GET" action="{{ route('recruitment.applications.index') }}" class="flex flex-wrap gap-2 mb-4">
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
        </form>

        @if($applications->isEmpty())
            <x-empty-state title="Belum ada pelamar" />
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                            <th class="py-2 px-2 font-medium">Kandidat</th>
                            <th class="py-2 pr-4 font-medium">Posisi</th>
                            <th class="py-2 pr-4 font-medium">Sumber</th>
                            <th class="py-2 pr-4 font-medium">Status</th>
                            <th class="py-2 pr-4 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($applications as $application)
                            <tr>
                                <td class="py-3 px-2">
                                    <span class="block font-medium text-slate-800">{{ $application->candidate_name }}</span>
                                    <span class="block text-xs text-slate-500">{{ $application->candidate_email }}</span>
                                </td>
                                <td class="py-3 pr-4 text-slate-600">{{ $application->jobPosting->title ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-600 text-xs">{{ $application->source ?? '—' }}</td>
                                <td class="py-3 pr-4"><x-status-badge :status="$application->status" /></td>
                                <td class="py-3 pr-4 text-right">
                                    <a href="{{ route('recruitment.applications.show', $application) }}" class="text-sm text-slate-600 hover:text-slate-900">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $applications->links() }}</div>
        @endif
    </x-card>
</x-app-layout>

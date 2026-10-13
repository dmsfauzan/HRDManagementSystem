<x-app-layout>
    <x-slot name="header">Lowongan</x-slot>

    <x-page-header title="Lowongan Kerja" subtitle="Kelola lowongan rekrutmen.">
        <x-slot:actions>
            <a href="{{ route('recruitment.applications.kanban') }}"><x-secondary-button>Pipeline</x-secondary-button></a>
            <a href="{{ route('career.index') }}" target="_blank"><x-secondary-button>Halaman Karir</x-secondary-button></a>
            @if($canManage)
                <a href="{{ route('recruitment.postings.create') }}"><x-primary-button>Buat Lowongan</x-primary-button></a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-card>
        @if($postings->isEmpty())
            <x-empty-state title="Belum ada lowongan" />
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                            <th class="py-2 px-2 font-medium">Kode</th>
                            <th class="py-2 pr-4 font-medium">Judul</th>
                            <th class="py-2 pr-4 font-medium">Departemen</th>
                            <th class="py-2 pr-4 font-medium">Pelamar</th>
                            <th class="py-2 pr-4 font-medium">Status</th>
                            <th class="py-2 pr-4 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($postings as $posting)
                            <tr>
                                <td class="py-3 px-2 font-mono text-xs text-slate-600">{{ $posting->code }}</td>
                                <td class="py-3 pr-4 font-medium text-slate-800">{{ $posting->title }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $posting->department->name ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $posting->applications_count }}</td>
                                <td class="py-3 pr-4"><x-status-badge :status="$posting->status" /></td>
                                <td class="py-3 pr-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('recruitment.applications.index', ['posting_id' => $posting->id]) }}" class="text-xs text-slate-600 hover:text-slate-900">Pelamar</a>
                                        @if($canManage)
                                            @if($posting->status !== 'published')
                                                <form method="POST" action="{{ route('recruitment.postings.publish', $posting) }}">@csrf @method('PATCH')
                                                    <button type="submit" class="text-xs px-2 py-0.5 rounded bg-emerald-600 text-white hover:bg-emerald-700">Terbitkan</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('recruitment.postings.close', $posting) }}">@csrf @method('PATCH')
                                                    <button type="submit" class="text-xs px-2 py-0.5 rounded bg-slate-200 text-slate-700 hover:bg-slate-300">Tutup</button>
                                                </form>
                                            @endif
                                            <a href="{{ route('recruitment.postings.edit', $posting) }}" class="text-xs text-slate-600 hover:text-slate-900">Ubah</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $postings->links() }}</div>
        @endif
    </x-card>
</x-app-layout>

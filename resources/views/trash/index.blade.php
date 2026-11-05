<x-app-layout>
    <x-slot name="header">Arsip</x-slot>

    <x-page-header title="Arsip Data" subtitle="Data yang dihapus (soft delete) dapat dipulihkan." />

    <div class="flex flex-wrap gap-2 mb-4">
        @foreach($allowed as $key => $config)
            <a href="{{ route('trash.index', ['type' => $key]) }}"
               class="px-3 py-1.5 rounded-lg text-sm border {{ $type === $key ? 'bg-emerald-50 text-emerald-700 border-emerald-200 font-medium' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
                {{ $config['label'] }}
            </a>
        @endforeach
    </div>

    <x-card>
        @if($records->isEmpty())
            <x-empty-state title="Arsip kosong" description="Tidak ada data terhapus untuk kategori ini." />
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                            <th class="py-2 px-2 font-medium">Nama</th>
                            <th class="py-2 pr-4 font-medium">Dihapus</th>
                            <th class="py-2 pr-4 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($records as $record)
                            <tr>
                                <td class="py-3 px-2 font-medium text-slate-800">{{ $record->{$titleColumn} ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-600 text-xs">{{ $record->deleted_at?->translatedFormat('d M Y H:i') }}</td>
                                <td class="py-3 pr-4 text-right">
                                    <form method="POST" action="{{ route('trash.restore', ['type' => $type, 'id' => $record->id]) }}" onsubmit="return confirm('Pulihkan data ini?')">@csrf @method('PATCH')
                                        <button type="submit" class="text-sm text-emerald-700 hover:underline">Pulihkan</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $records->links() }}</div>
        @endif
    </x-card>
</x-app-layout>

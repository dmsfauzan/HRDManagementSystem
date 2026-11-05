<x-app-layout>
    <x-slot name="header">Pengumuman</x-slot>

    <x-page-header title="Pengumuman HR" subtitle="Informasi dan pengumuman resmi perusahaan.">
        <x-slot:actions>
            @if($canManage)
                <a href="{{ route('announcements.create') }}"><x-primary-button>Buat Pengumuman</x-primary-button></a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if($announcements->isEmpty())
        <x-card><x-empty-state title="Belum ada pengumuman" /></x-card>
    @else
        <div class="space-y-4">
            @foreach($announcements as $announcement)
                <x-card>
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-semibold text-slate-900">{{ $announcement->title }}</h3>
                                @if($canManage)
                                    <x-status-badge :status="$announcement->is_active ? 'active' : 'inactive'" />
                                    @unless($announcement->is_active)
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-700">Draf</span>
                                    @endunless
                                @endif
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ $announcement->author->name ?? 'Sistem' }}
                                @if($announcement->published_at) &middot; {{ $announcement->published_at->translatedFormat('d M Y H:i') }} @endif
                                @if($canManage && isset($announcement->reads_count)) &middot; {{ $announcement->reads_count }} pembaca @endif
                            </p>
                            <p class="mt-3 text-sm text-slate-700 whitespace-pre-line">{{ $announcement->body }}</p>
                        </div>
                        @if($canManage)
                            <div class="flex flex-col items-end gap-2 shrink-0">
                                @unless($announcement->is_active)
                                    <form method="POST" action="{{ route('announcements.publish', $announcement) }}">@csrf @method('PATCH')
                                        <button type="submit" class="text-xs px-2.5 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700">Terbitkan</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('announcements.unpublish', $announcement) }}">@csrf @method('PATCH')
                                        <button type="submit" class="text-xs px-2.5 py-1 rounded bg-slate-200 text-slate-700 hover:bg-slate-300">Tarik</button>
                                    </form>
                                @endunless
                                <form method="POST" action="{{ route('announcements.destroy', $announcement) }}">@csrf @method('DELETE')
                                    <x-confirm-modal title="Hapus Pengumuman" message="Pengumuman ini akan dihapus permanen." confirm-label="Hapus">
                                        <x-slot:trigger><button type="button" class="text-xs text-red-600 hover:text-red-800">Hapus</button></x-slot:trigger>
                                        <x-slot:action><button type="submit" class="px-4 py-2 text-sm font-medium rounded-lg bg-red-600 text-white hover:bg-red-700">Hapus</button></x-slot:action>
                                    </x-confirm-modal>
                                </form>
                            </div>
                        @endif
                    </div>
                </x-card>
            @endforeach
        </div>
        <div class="mt-4">{{ $announcements->links() }}</div>
    @endif
</x-app-layout>

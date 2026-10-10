<x-app-layout>
    <x-slot name="header">Notifikasi</x-slot>

    <x-page-header title="Notifikasi" subtitle="Seluruh pemberitahuan untuk Anda.">
        <x-slot:actions>
            <form method="POST" action="{{ route('notifications.readAll') }}">@csrf @method('PATCH')
                <x-secondary-button type="submit">Tandai Semua Dibaca</x-secondary-button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <div class="divide-y divide-slate-100">
            @forelse($notifications as $notif)
                <div class="flex items-start gap-3 py-4">
                    <span class="mt-1.5 w-2.5 h-2.5 rounded-full {{ $notif->read_at ? 'bg-slate-300' : 'bg-emerald-500' }}"></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm {{ $notif->read_at ? 'text-slate-500' : 'text-slate-800 font-medium' }}">{{ $notif->data['message'] ?? 'Notifikasi baru' }}</p>
                        <p class="text-xs text-slate-400 mt-1">{{ $notif->created_at->translatedFormat('d M Y H:i') }} &middot; {{ $notif->created_at->diffForHumans() }}</p>
                    </div>
                    @unless($notif->read_at)
                        <form method="POST" action="{{ route('notifications.read', $notif->id) }}">@csrf @method('PATCH')
                            <button type="submit" class="text-xs text-emerald-700 hover:underline">Tandai dibaca</button>
                        </form>
                    @endunless
                </div>
            @empty
                <x-empty-state title="Tidak ada notifikasi" />
            @endforelse
        </div>
        <div class="mt-4">{{ $notifications->links() }}</div>
    </x-card>
</x-app-layout>

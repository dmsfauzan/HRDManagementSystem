<x-app-layout>
    <x-slot name="header">Sesi Aktif</x-slot>

    <x-page-header title="Sesi & Perangkat Aktif" subtitle="Kelola sesi login Anda. Cabut sesi yang tidak dikenal.">
        <x-slot:actions>
            <a href="{{ route('profile.edit') }}"><x-secondary-button>Kembali ke Profil</x-secondary-button></a>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        @if($sessions->isEmpty())
            <x-empty-state title="Tidak ada sesi tercatat" />
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                            <th class="py-2 px-2 font-medium">Perangkat</th>
                            <th class="py-2 pr-4 font-medium">Alamat IP</th>
                            <th class="py-2 pr-4 font-medium">Terakhir Aktif</th>
                            <th class="py-2 pr-4 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($sessions as $session)
                            <tr class="hover:bg-slate-50/60">
                                <td class="py-3 px-2">
                                    <span class="block text-slate-800">{{ \Illuminate\Support\Str::limit($session->user_agent ?: 'Perangkat tidak dikenal', 70) }}</span>
                                    @if($session->is_current)
                                        <span class="inline-flex items-center gap-1 mt-1 text-[10px] px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Sesi ini
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 pr-4 text-slate-600">{{ $session->ip_address ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $session->last_active_at->diffForHumans() }}</td>
                                <td class="py-3 pr-4 text-right">
                                    @unless($session->is_current)
                                        <form method="POST" action="{{ route('sessions.destroy', $session->id) }}" class="inline">@csrf @method('DELETE')
                                            <x-confirm-modal title="Cabut Sesi" message="Sesi ini akan diakhiri dan perangkat harus login ulang." confirm-label="Cabut">
                                                <x-slot:trigger><button type="button" class="text-sm text-red-600 hover:text-red-800">Cabut</button></x-slot:trigger>
                                                <x-slot:action><button type="submit" class="px-4 py-2 text-sm font-medium rounded-lg bg-red-600 text-white hover:bg-red-700">Cabut</button></x-slot:action>
                                            </x-confirm-modal>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400">Aktif</span>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-app-layout>

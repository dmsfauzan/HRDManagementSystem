<x-app-layout>
    <x-slot name="header">Token API</x-slot>

    <x-page-header title="Token API" subtitle="Token untuk mengakses REST API HRIS (/api/v1).">
        <x-slot:actions>
            <a href="{{ route('profile.edit') }}"><x-secondary-button>Kembali ke Profil</x-secondary-button></a>
        </x-slot:actions>
    </x-page-header>

    @if($plainToken)
        <x-card class="mb-6" title="Token Baru Dibuat">
            <p class="text-sm text-slate-600">Salin token ini sekarang — tidak akan ditampilkan lagi.</p>
            <pre class="mt-3 p-3 bg-slate-900 text-emerald-300 rounded-lg text-xs overflow-x-auto">{{ $plainToken }}</pre>
            <p class="mt-2 text-xs text-slate-500">Gunakan token <strong>apa adanya</strong> pada header <code class="font-mono">Authorization: Bearer &lt;token&gt;</code> — jangan menambahkan <code class="font-mono">id|</code> atau spasi.</p>
        </x-card>
    @endif

    <x-card title="Buat Token Baru" class="mb-6">
        <form method="POST" action="{{ route('api-tokens.store') }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div class="flex-1 min-w-[200px]">
                <x-input-label for="name" value="Nama Token *" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" placeholder="cth: Aplikasi Mobile" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="expires_in_days" value="Berlaku (hari)" />
                <x-text-input id="expires_in_days" name="expires_in_days" type="number" min="1" max="3650" class="mt-1 block w-32" :value="old('expires_in_days')" placeholder="∞" />
            </div>
            <x-primary-button>Buat Token</x-primary-button>
        </form>
    </x-card>

    <x-card title="Token Aktif">
        @if($tokens->isEmpty())
            <x-empty-state title="Belum ada token" />
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                            <th class="py-2 px-2 font-medium">Nama</th>
                            <th class="py-2 pr-4 font-medium">Dibuat</th>
                            <th class="py-2 pr-4 font-medium">Terakhir Dipakai</th>
                            <th class="py-2 pr-4 font-medium">Kedaluwarsa</th>
                            <th class="py-2 pr-4 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($tokens as $token)
                            <tr>
                                <td class="py-3 px-2 font-medium text-slate-800">{{ $token->name }}</td>
                                <td class="py-3 pr-4 text-slate-600 text-xs">{{ $token->created_at->translatedFormat('d M Y') }}</td>
                                <td class="py-3 pr-4 text-slate-600 text-xs">{{ $token->last_used_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-600 text-xs">{{ $token->expires_at?->translatedFormat('d M Y') ?? 'Tidak kedaluwarsa' }}</td>
                                <td class="py-3 pr-4 text-right">
                                    <form method="POST" action="{{ route('api-tokens.destroy', $token) }}" onsubmit="return confirm('Cabut token ini?')">@csrf @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">Cabut</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-app-layout>

<x-guest-layout>
    <h2 class="text-lg font-semibold text-slate-900">Verifikasi Dua Faktor</h2>
    <p class="mt-1 text-sm text-slate-600">Masukkan 6 digit kode yang dikirim ke email Anda.</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('two-factor.verify') }}" class="mt-6">
        @csrf

        <div>
            <x-input-label for="code" value="Kode Verifikasi" />
            <x-text-input id="code" name="code" type="text" inputmode="numeric" maxlength="6"
                class="block mt-1 w-full tracking-[0.5em] text-center text-lg" required autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>Verifikasi</x-primary-button>
        </div>
    </form>

    <div class="flex items-center justify-between mt-4">
        <form method="POST" action="{{ route('two-factor.resend') }}">
            @csrf
            <button type="submit" class="text-sm text-emerald-700 hover:underline">Kirim ulang kode</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-xs text-slate-500 hover:underline">Batalkan</button>
        </form>
    </div>
</x-guest-layout>

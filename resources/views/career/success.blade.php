<x-public-layout :title="'Lamaran Terkirim'">
    <div class="max-w-lg mx-auto bg-white rounded-xl border border-slate-200 p-8 text-center">
        <div class="w-14 h-14 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="mt-4 text-lg font-semibold text-slate-900">Lamaran Anda Terkirim</h1>
        <p class="mt-2 text-sm text-slate-600">
            Terima kasih telah melamar. Kami akan menghubungi Anda melalui email
            @if(session('application_email')) <strong>{{ session('application_email') }}</strong> @endif
            jika profil Anda sesuai.
        </p>
        <a href="{{ route('career.index') }}" class="mt-6 inline-block text-sm text-emerald-700 hover:underline">Lihat lowongan lain</a>
    </div>
</x-public-layout>

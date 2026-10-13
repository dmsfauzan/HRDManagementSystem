<x-public-layout :title="'Lamaran Terkirim'">
    <div class="max-w-lg mx-auto bg-white rounded-xl border border-slate-200 p-8 text-center">
        <div class="w-14 h-14 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="mt-4 text-lg font-semibold text-slate-900">Lamaran Anda Terkirim</h1>
        <p class="mt-2 text-sm text-slate-600">
            Terima kasih, {{ $application->candidate_name }}. Lamaran untuk posisi
            <strong>{{ $application->jobPosting->title ?? '-' }}</strong> telah kami terima.
        </p>

        <div class="mt-6 p-4 rounded-lg bg-slate-50 text-left">
            <p class="text-xs text-slate-500">Simpan tautan ini untuk memantau status lamaran Anda:</p>
            <a href="{{ route('career.status', $application->status_token) }}" class="mt-1 block text-sm text-emerald-700 hover:underline break-all">{{ route('career.status', $application->status_token) }}</a>
        </div>

        <a href="{{ route('career.status', $application->status_token) }}" class="mt-6 inline-block px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700">Pantau Status</a>
    </div>
</x-public-layout>

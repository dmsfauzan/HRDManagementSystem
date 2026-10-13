<x-public-layout :title="'Status Lamaran'">
    @php
        $stages = ['applied' => 'Lamaran Diterima', 'screening' => 'Screening', 'interview' => 'Wawancara', 'offer' => 'Penawaran', 'hired' => 'Diterima'];
        $labels = ['applied' => 'Diterima', 'screening' => 'Screening', 'interview' => 'Wawancara', 'offer' => 'Penawaran', 'hired' => 'Diterima', 'rejected' => 'Tidak Lolos', 'withdrawn' => 'Dibatalkan'];
        $reached = $application->stageLogs->pluck('to_status')->all();
    @endphp

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <p class="text-xs text-slate-500">Pelamar</p>
            <h1 class="text-lg font-bold text-slate-900">{{ $application->candidate_name }}</h1>
            <p class="text-sm text-slate-600">Posisi: {{ $application->jobPosting->title ?? '-' }}</p>
            <p class="mt-3 text-sm">Status saat ini:
                <x-status-badge :status="$application->status" />
            </p>

            @if($application->rejection_reason)
                <div class="mt-3 p-3 rounded-lg bg-red-50 text-sm text-red-700">Catatan: {{ $application->rejection_reason }}</div>
            @endif
        </div>

        @if(! in_array($application->status, ['rejected', 'withdrawn'], true))
            <div class="bg-white rounded-xl border border-slate-200 p-6 mt-6">
                <h2 class="text-sm font-semibold text-slate-900 mb-4">Tahapan Seleksi</h2>
                <ol class="space-y-4">
                    @foreach($stages as $key => $label)
                        <li class="flex items-start gap-3">
                            <span class="mt-1 w-3 h-3 rounded-full {{ in_array($key, $reached, true) ? 'bg-emerald-500' : 'bg-slate-200' }}"></span>
                            <span class="text-sm {{ in_array($key, $reached, true) ? 'text-slate-800 font-medium' : 'text-slate-400' }}">{{ $label }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        <div class="bg-white rounded-xl border border-slate-200 p-6 mt-6">
            <h2 class="text-sm font-semibold text-slate-900 mb-3">Riwayat</h2>
            <div class="space-y-3">
                @foreach($application->stageLogs as $log)
                    <div class="flex items-start gap-2">
                        <span class="mt-1.5 w-2 h-2 rounded-full bg-slate-300"></span>
                        <div>
                            <p class="text-sm text-slate-800">{{ $labels[$log->to_status] ?? \Illuminate\Support\Str::headline($log->to_status) }}</p>
                            <p class="text-xs text-slate-500">{{ $log->acted_at?->translatedFormat('d M Y H:i') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-public-layout>

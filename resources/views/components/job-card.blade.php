@props(['posting'])
@php
    $modeLabels = ['onsite' => 'Onsite', 'hybrid' => 'Hybrid', 'remote' => 'Remote'];
    $levelLabels = ['intern' => 'Intern', 'junior' => 'Junior', 'mid' => 'Mid', 'senior' => 'Senior', 'lead' => 'Lead'];
    $typeLabels = ['permanent' => 'Tetap', 'contract' => 'Kontrak', 'intern' => 'Magang', 'probation' => 'Percobaan'];
@endphp
<a href="{{ route('career.show', $posting) }}" class="block bg-white rounded-xl border border-slate-200 p-5 hover:border-emerald-300 hover:shadow-sm transition">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <h2 class="text-base font-semibold text-slate-900 truncate">{{ $posting->title }}</h2>
            <p class="text-sm text-slate-500 mt-0.5">
                {{ $posting->department->name ?? '—' }}
                @if($posting->location) &middot; {{ $posting->location }} @endif
            </p>
        </div>
        @if($posting->isOpen())
            <span class="text-xs px-2 py-1 rounded-full bg-emerald-50 text-emerald-700 shrink-0">Dibuka</span>
        @else
            <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-500 shrink-0">Ditutup</span>
        @endif
    </div>

    <div class="mt-3 flex flex-wrap gap-1.5">
        <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ $modeLabels[$posting->work_mode] ?? $posting->work_mode }}</span>
        <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ $levelLabels[$posting->experience_level] ?? $posting->experience_level }}</span>
        <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ $typeLabels[$posting->employment_type] ?? $posting->employment_type }}</span>
        @if($posting->show_salary && $posting->salary_min)
            <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">Rp {{ number_format((float) $posting->salary_min, 0, ',', '.') }}@if($posting->salary_max)–{{ number_format((float) $posting->salary_max, 0, ',', '.') }}@endif</span>
        @endif
    </div>

    <p class="mt-3 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit(strip_tags($posting->description), 140) }}</p>

    @if($posting->closes_at)
        <p class="mt-3 text-xs {{ $posting->isOpen() ? 'text-amber-600' : 'text-slate-400' }}">Batas lamaran: {{ $posting->closes_at->translatedFormat('d M Y') }}</p>
    @endif
</a>

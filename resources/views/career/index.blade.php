<x-public-layout :title="'Lowongan'">
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-slate-900">Bergabung dengan Tim Kami</h1>
        <p class="mt-2 text-sm text-slate-600">Temukan peluang karier yang sesuai dengan Anda.</p>
    </div>

    @if($postings->isEmpty())
        <div class="bg-white rounded-xl border border-slate-200 p-10 text-center text-sm text-slate-500">
            Belum ada lowongan yang dibuka saat ini.
        </div>
    @else
        <div class="space-y-4">
            @foreach($postings as $posting)
                <a href="{{ route('career.show', $posting) }}" class="block bg-white rounded-xl border border-slate-200 p-5 hover:border-emerald-300 transition">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">{{ $posting->title }}</h2>
                            <p class="text-sm text-slate-500 mt-0.5">
                                {{ $posting->department->name ?? '—' }}
                                @if($posting->location) &middot; {{ $posting->location }} @endif
                                &middot; {{ \Illuminate\Support\Str::headline($posting->employment_type) }}
                            </p>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full bg-emerald-50 text-emerald-700 shrink-0">Dibuka</span>
                    </div>
                    <p class="mt-3 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit(strip_tags($posting->description), 160) }}</p>
                </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $postings->links() }}</div>
    @endif
</x-public-layout>

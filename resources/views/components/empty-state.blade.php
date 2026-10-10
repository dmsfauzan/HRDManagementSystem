@props(['title' => 'Tidak ada data', 'description' => null, 'icon' => null])
<div class="flex flex-col items-center justify-center text-center py-12 px-6">
    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-4">
        @if($icon)
            {{ $icon }}
        @else
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
        @endif
    </div>
    <p class="text-sm font-medium text-slate-700">{{ $title }}</p>
    @if($description)
        <p class="mt-1 text-sm text-slate-500 max-w-sm">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-4">{{ $action }}</div>
    @endisset
</div>

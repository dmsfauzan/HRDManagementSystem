@props(['label', 'value', 'hint' => null, 'accent' => 'emerald'])
@php
    $accentClasses = match ($accent) {
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'blue' => 'bg-blue-50 text-blue-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'red' => 'bg-red-50 text-red-600',
        'violet' => 'bg-violet-50 text-violet-600',
        'slate' => 'bg-slate-100 text-slate-600',
        default => 'bg-emerald-50 text-emerald-600',
    };
@endphp
<div class="bg-white rounded-xl border border-slate-200 p-5">
    <div class="flex items-start justify-between">
        <div>
            <p class="text-xs font-medium tracking-wide text-slate-500 uppercase">{{ $label }}</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $value }}</p>
            @if($hint)
                <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
            @endif
        </div>
        @isset($icon)
            <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $accentClasses }}">
                {{ $icon }}
            </div>
        @endisset
    </div>
</div>

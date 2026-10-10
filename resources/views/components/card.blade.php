@props(['title' => null])
<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-slate-200']) }}>
    @if($title || isset($header))
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            @if($title)<h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>@endif
            {{ $header ?? '' }}
        </div>
    @endif
    <div class="p-5">
        {{ $slot }}
    </div>
</div>

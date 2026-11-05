@props(['items' => []])
@if(count($items))
    <nav aria-label="Breadcrumb" class="mb-4">
        <ol class="flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
            @foreach($items as $item)
                <li class="flex items-center gap-1.5">
                    @if(! $loop->last && ! empty($item['url']))
                        <a href="{{ $item['url'] }}" class="hover:text-slate-800">{{ $item['label'] }}</a>
                    @else
                        <span class="{{ $loop->last ? 'text-slate-700 font-medium' : '' }}">{{ $item['label'] }}</span>
                    @endif
                    @unless($loop->last)
                        <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    @endunless
                </li>
            @endforeach
        </ol>
    </nav>
@endif

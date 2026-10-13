@props(['employee', 'byManager', 'depth' => 0])
@php($children = $byManager->get($employee->id, collect()))
<li class="relative pl-6">
    <span class="absolute left-0 top-3 w-4 h-px bg-slate-300"></span>
    <div class="inline-flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2 mb-1">
        @if($employee->photo_path)
            <img src="{{ route('employees.photo', $employee) }}" alt="" class="w-8 h-8 rounded-full object-cover">
        @else
            <span class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-semibold">{{ strtoupper(substr($employee->full_name, 0, 1)) }}</span>
        @endif
        <div class="min-w-0">
            <a href="{{ route('employees.show', $employee) }}" class="block text-sm font-medium text-slate-800 hover:text-emerald-700">{{ $employee->full_name }}</a>
            <span class="block text-xs text-slate-500">{{ $employee->position->name ?? '—' }} &middot; {{ $employee->department->name ?? '—' }}</span>
        </div>
        <x-status-badge :status="$employee->employment_status" class="ml-2" />
    </div>
    @if($children->isNotEmpty() && $depth < 6)
        <ul class="border-l border-slate-200 ml-1">
            @foreach($children as $child)
                <x-org-node :employee="$child" :by-manager="$byManager" :depth="$depth + 1" />
            @endforeach
        </ul>
    @endif
</li>

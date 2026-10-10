<x-app-layout>
    <x-slot name="header">Departemen</x-slot>
    <x-page-header :title="$department->name" :subtitle="$department->code" />
    <x-card>
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Kode</dt><dd class="font-mono text-xs text-slate-800">{{ $department->code }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Nama</dt><dd class="text-slate-800">{{ $department->name }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Status</dt><dd><x-status-badge :status="$department->is_active ? 'active' : 'inactive'" /></dd></div>
        </dl>
        @if($department->description)
            <p class="text-sm text-slate-600 mt-4">{{ $department->description }}</p>
        @endif

        <p class="text-sm font-medium text-slate-900 mt-6">Jabatan di departemen ini</p>
        <div class="space-y-2 mt-3">
            @forelse($department->positions as $position)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-700">{{ $position->name }} <span class="text-xs text-slate-400">({{ $position->code }})</span></span>
                    <x-status-badge :status="$position->is_active ? 'active' : 'inactive'" />
                </div>
            @empty
                <x-empty-state title="Belum ada jabatan" />
            @endforelse
        </div>
    </x-card>
</x-app-layout>

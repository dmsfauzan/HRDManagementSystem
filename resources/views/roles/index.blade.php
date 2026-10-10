<x-app-layout>
    <x-slot name="header">Role</x-slot>

    <x-page-header title="Role & Permission" subtitle="Daftar role dan izin yang dimilikinya.">
        <x-slot:actions>
            <a href="{{ route('users.index') }}"><x-secondary-button>Kembali ke Pengguna</x-secondary-button></a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @foreach($roles as $role)
            <x-card :title="\Illuminate\Support\Str::headline($role->name)">
                <p class="text-xs text-slate-500 mb-3">{{ $role->users_count }} pengguna</p>
                <div class="flex flex-wrap gap-1.5">
                    @forelse($role->permissions as $permission)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-slate-100 text-slate-700">{{ $permission->name }}</span>
                    @empty
                        <span class="text-sm text-slate-400">Belum ada permission</span>
                    @endforelse
                </div>
            </x-card>
        @endforeach
    </div>

    <p class="text-xs text-slate-500 mt-6">Catatan: pengaturan permission granular per role dapat dikembangkan lebih lanjut. Role <span class="font-mono">super_admin</span> memiliki seluruh permission sistem.</p>
</x-app-layout>

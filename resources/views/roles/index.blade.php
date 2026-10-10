<x-app-layout>
    <x-slot name="header">Role</x-slot>

    <x-page-header title="Role & Permission" subtitle="Atur permission untuk tiap role. Khusus Super Admin.">
        <x-slot:actions>
            <a href="{{ route('users.index') }}"><x-secondary-button>Kembali ke Pengguna</x-secondary-button></a>
        </x-slot:actions>
    </x-page-header>

    <div class="space-y-6">
        @foreach($roles as $role)
            @php($isSuper = $role->name === 'super_admin')
            @php($rolePerms = $role->permissions->pluck('name')->all())
            <x-card>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">{{ \Illuminate\Support\Str::headline($role->name) }}</h3>
                        <p class="text-xs text-slate-500">{{ $role->users_count }} pengguna &middot; {{ count($rolePerms) }} permission</p>
                    </div>
                    @if($isSuper)
                        <span class="text-[10px] px-2 py-0.5 rounded bg-amber-100 text-amber-700">Selalu akses penuh</span>
                    @endif
                </div>

                <form method="POST" action="{{ route('roles.permissions.update', $role) }}">
                    @csrf @method('PATCH')
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-3">
                        @foreach($permissions as $group => $items)
                            <div>
                                <p class="text-[11px] font-semibold tracking-wide text-slate-400 uppercase mb-1">{{ $group }}</p>
                                <div class="space-y-1">
                                    @foreach($items as $permission)
                                        <label class="flex items-center gap-2 text-sm {{ $isSuper ? 'opacity-60' : '' }}">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                                @checked(in_array($permission->name, $rolePerms, true))
                                                @disabled($isSuper)
                                                class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                            <span class="font-mono text-xs text-slate-700">{{ $permission->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @unless($isSuper)
                        <div class="mt-5">
                            <x-primary-button>Simpan Permission</x-primary-button>
                        </div>
                    @endunless
                </form>
            </x-card>
        @endforeach
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">Pengguna</x-slot>

    <x-page-header title="Pengguna" subtitle="Kelola akun, role, dan status aktif pengguna.">
        <x-slot:actions>
            @can('create', \App\Models\User::class)
                <a href="{{ route('users.create') }}"><x-primary-button>Tambah Pengguna</x-primary-button></a>
                <a href="{{ route('roles.index') }}"><x-secondary-button>Lihat Role</x-secondary-button></a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <form method="GET" action="{{ route('users.index') }}" class="flex flex-wrap gap-2 mb-4">
            <x-text-input name="search" :value="request('search')" placeholder="Cari nama atau email..." class="flex-1 min-w-[200px]" />
            <x-select-input name="role" onchange="this.form.submit()">
                <option value="">Semua role</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ \Illuminate\Support\Str::headline($role->name) }}</option>
                @endforeach
            </x-select-input>
            <x-select-input name="status" onchange="this.form.submit()">
                <option value="">Semua status</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
            </x-select-input>
            <x-secondary-button type="submit">Cari</x-secondary-button>
        </form>

        @if($users->isEmpty())
            <x-empty-state title="Tidak ada pengguna" />
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                            <th class="py-2 px-2 font-medium">Nama</th>
                            <th class="py-2 pr-4 font-medium">Role</th>
                            <th class="py-2 pr-4 font-medium">Karyawan Terkait</th>
                            <th class="py-2 pr-4 font-medium">Status</th>
                            <th class="py-2 pr-4 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($users as $user)
                            <tr class="hover:bg-slate-50/60">
                                <td class="py-3 px-2">
                                    <span class="block font-medium text-slate-800">{{ $user->name }}</span>
                                    <span class="block text-xs text-slate-500">{{ $user->email }}</span>
                                </td>
                                <td class="py-3 pr-4 text-slate-600">{{ \Illuminate\Support\Str::headline($user->roles->first()?->name ?? '-') }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $user->employee->full_name ?? '—' }}</td>
                                <td class="py-3 pr-4"><x-status-badge :status="$user->is_active ? 'active' : 'inactive'" /></td>
                                <td class="py-3 pr-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        @can('update', $user)
                                            <a href="{{ route('users.edit', $user) }}" class="text-sm text-slate-600 hover:text-slate-900">Ubah</a>
                                            <form method="POST" action="{{ route('users.toggleActive', $user) }}">@csrf @method('PATCH')
                                                <button type="submit" class="text-sm {{ $user->is_active ? 'text-red-600 hover:text-red-800' : 'text-emerald-600 hover:text-emerald-800' }}">
                                                    {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $users->links() }}</div>
        @endif
    </x-card>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">Ubah Pengguna</x-slot>

    <x-page-header :title="'Ubah: ' . $user->name" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <form method="POST" action="{{ route('users.update', $user) }}" class="lg:col-span-2">
            @csrf @method('PUT')
            <x-card title="Data Akun">
                <div class="space-y-4">
                    <div>
                        <x-input-label for="name" value="Nama *" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $user->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="email" value="Email *" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="role" value="Role *" />
                        <x-select-input id="role" name="role" class="mt-1 block w-full">
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" @selected(old('role', $user->getRoleNames()->first()) === $role->name)>{{ \Illuminate\Support\Str::headline($role->name) }}</option>
                            @endforeach
                        </x-select-input>
                        @if($user->id === auth()->id())
                            <p class="text-xs text-amber-600 mt-1">Anda tidak dapat mengubah role akun Anda sendiri.</p>
                        @endif
                        <x-input-error :messages="$errors->get('role')" class="mt-1" />
                    </div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $user->is_active)) class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-sm text-slate-700">Akun aktif</span>
                    </label>
                </div>
            </x-card>
            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Simpan Perubahan</x-primary-button>
                <a href="{{ route('users.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
            </div>
        </form>

        <div class="space-y-6">
            <x-card title="Reset Password">
                @can('resetPassword', $user)
                    <form method="POST" action="{{ route('users.resetPassword', $user) }}" class="space-y-4">
                        @csrf @method('PATCH')
                        <div>
                            <x-input-label for="password" value="Password Baru" />
                            <x-text-input id="password" name="password" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('password')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="password_confirmation" value="Konfirmasi Password" />
                            <x-text-input id="password_confirmation" name="password_confirmation" type="text" class="mt-1 block w-full" required />
                        </div>
                        <x-secondary-button type="submit">Reset Password</x-secondary-button>
                    </form>
                @else
                    <p class="text-sm text-slate-500">Tidak tersedia untuk akun Anda sendiri.</p>
                @endcan
            </x-card>

            @if($user->employee)
                <x-card title="Karyawan Terkait">
                    <a href="{{ route('employees.show', $user->employee) }}" class="text-sm text-emerald-700 hover:underline">{{ $user->employee->full_name }} ({{ $user->employee->employee_id }})</a>
                </x-card>
            @endif
        </div>
    </div>
</x-app-layout>

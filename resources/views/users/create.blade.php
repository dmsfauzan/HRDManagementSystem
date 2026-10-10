<x-app-layout>
    <x-slot name="header">Tambah Pengguna</x-slot>

    <x-page-header title="Tambah Pengguna" subtitle="Buat akun login baru dan tentukan role-nya." />

    <form method="POST" action="{{ route('users.store') }}" class="max-w-2xl">
        @csrf
        <x-card>
            <div class="space-y-4">
                <div>
                    <x-input-label for="name" value="Nama *" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="email" value="Email *" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="password" value="Password *" />
                    <x-text-input id="password" name="password" type="text" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="role" value="Role *" />
                    <x-select-input id="role" name="role" class="mt-1 block w-full">
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" @selected(old('role') === $role->name)>{{ \Illuminate\Support\Str::headline($role->name) }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('role')" class="mt-1" />
                </div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', true)) class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-slate-700">Akun aktif</span>
                </label>
            </div>
        </x-card>
        <div class="flex items-center gap-3 mt-6">
            <x-primary-button>Simpan</x-primary-button>
            <a href="{{ route('users.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
        </div>
    </form>
</x-app-layout>

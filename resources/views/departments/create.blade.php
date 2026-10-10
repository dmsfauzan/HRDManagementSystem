<x-app-layout>
    <x-slot name="header">Departemen</x-slot>
    <x-page-header title="Tambah Departemen" />
    <form method="POST" action="{{ route('departments.store') }}" class="max-w-2xl">
        @csrf
        <x-card>
            <div class="space-y-4">
                <div>
                    <x-input-label for="code" value="Kode *" />
                    <x-text-input id="code" name="code" class="mt-1 block w-full" :value="old('code')" required />
                    <x-input-error :messages="$errors->get('code')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="name" value="Nama Departemen *" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="description" value="Deskripsi" />
                    <x-textarea-input id="description" name="description" rows="3" class="mt-1">{{ old('description') }}</x-textarea-input>
                    <x-input-error :messages="$errors->get('description')" class="mt-1" />
                </div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', true)) class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-slate-700">Aktif</span>
                </label>
            </div>
        </x-card>
        <div class="flex items-center gap-3 mt-6">
            <x-primary-button>Simpan</x-primary-button>
            <a href="{{ route('departments.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
        </div>
    </form>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">Ubah Hari Libur</x-slot>
    <x-page-header title="Ubah Hari Libur" />
    <form method="POST" action="{{ route('holidays.update', $holiday) }}" class="max-w-xl">
        @csrf @method('PUT')
        <x-card>
            <div class="space-y-4">
                <div>
                    <x-input-label for="date" value="Tanggal *" />
                    <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', $holiday->date->format('Y-m-d'))" required />
                    <x-input-error :messages="$errors->get('date')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="name" value="Nama Hari Libur *" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $holiday->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_national" value="1" @checked((bool) old('is_national', $holiday->is_national)) class="rounded border-slate-300 text-emerald-600">
                    <span class="text-sm text-slate-700">Libur nasional</span>
                </label>
            </div>
        </x-card>
        <div class="flex items-center gap-3 mt-6">
            <x-primary-button>Simpan Perubahan</x-primary-button>
            <a href="{{ route('holidays.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
        </div>
    </form>
</x-app-layout>

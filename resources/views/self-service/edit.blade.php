<x-app-layout>
    <x-slot name="header">Data Pribadi</x-slot>

    <x-page-header :title="'Data Pribadi — ' . $employee->full_name" subtitle="Perbarui informasi pribadi Anda. Data kepegawaian hanya dapat diubah HRD." />

    <form method="POST" action="{{ route('profile.employee.update') }}" class="max-w-2xl">
        @csrf @method('PUT')
        <x-card>
            <div class="space-y-4">
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label value="Employee ID" />
                        <x-text-input class="mt-1 block w-full" :value="$employee->employee_id" disabled />
                    </div>
                    <div>
                        <x-input-label value="Tanggal Mulai Kerja" />
                        <x-text-input class="mt-1 block w-full" :value="$employee->join_date?->format('d/m/Y')" disabled />
                    </div>
                </div>
                <div>
                    <x-input-label for="phone" value="Nomor Telepon" />
                    <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $employee->phone)" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="address" value="Alamat" />
                    <x-textarea-input id="address" name="address" rows="3" class="mt-1">{{ old('address', $employee->address) }}</x-textarea-input>
                    <x-input-error :messages="$errors->get('address')" class="mt-1" />
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="birth_date" value="Tanggal Lahir" />
                        <x-text-input id="birth_date" name="birth_date" type="date" class="mt-1 block w-full" :value="old('birth_date', $employee->birth_date?->format('Y-m-d'))" />
                        <x-input-error :messages="$errors->get('birth_date')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="gender" value="Jenis Kelamin" />
                        <x-select-input id="gender" name="gender" class="mt-1 block w-full">
                            <option value="">— Pilih —</option>
                            <option value="male" @selected(old('gender', $employee->gender) === 'male')>Laki-laki</option>
                            <option value="female" @selected(old('gender', $employee->gender) === 'female')>Perempuan</option>
                        </x-select-input>
                        <x-input-error :messages="$errors->get('gender')" class="mt-1" />
                    </div>
                </div>
            </div>
        </x-card>
        <div class="flex items-center gap-3 mt-6">
            <x-primary-button>Simpan Perubahan</x-primary-button>
            <a href="{{ route('dashboard') }}"><x-secondary-button type="button">Kembali</x-secondary-button></a>
        </div>
    </form>
</x-app-layout>

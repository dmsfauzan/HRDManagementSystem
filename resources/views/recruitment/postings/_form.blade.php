@php($posting = $posting ?? null)
<x-app-layout>
    <x-slot name="header">Lowongan</x-slot>
    <x-page-header :title="$posting ? 'Ubah Lowongan' : 'Buat Lowongan'" />

    <form method="POST" action="{{ $posting ? route('recruitment.postings.update', $posting) : route('recruitment.postings.store') }}" class="max-w-3xl">
        @csrf
        @if($posting) @method('PUT') @endif

        <x-card>
            <div class="space-y-4">
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="code" value="Kode *" />
                        <x-text-input id="code" name="code" class="mt-1 block w-full" :value="old('code', $posting?->code)" required />
                        <x-input-error :messages="$errors->get('code')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="title" value="Judul *" />
                        <x-text-input id="title" name="title" class="mt-1 block w-full" :value="old('title', $posting?->title)" required />
                        <x-input-error :messages="$errors->get('title')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="department_id" value="Departemen" />
                        <x-select-input id="department_id" name="department_id" class="mt-1 block w-full">
                            <option value="">— Pilih —</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" @selected((int) old('department_id', $posting?->department_id) === $dept->id)>{{ $dept->name }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="position_id" value="Jabatan" />
                        <x-select-input id="position_id" name="position_id" class="mt-1 block w-full">
                            <option value="">— Pilih —</option>
                            @foreach($positions as $pos)
                                <option value="{{ $pos->id }}" @selected((int) old('position_id', $posting?->position_id) === $pos->id)>{{ $pos->name }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="location" value="Lokasi" />
                        <x-text-input id="location" name="location" class="mt-1 block w-full" :value="old('location', $posting?->location)" />
                    </div>
                    <div>
                        <x-input-label for="employment_type" value="Tipe Kepegawaian *" />
                        <x-select-input id="employment_type" name="employment_type" class="mt-1 block w-full">
                            @foreach(['permanent' => 'Tetap', 'contract' => 'Kontrak', 'intern' => 'Magang', 'probation' => 'Percobaan'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('employment_type', $posting?->employment_type ?? 'permanent') === $value)>{{ $label }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="quota" value="Kuota *" />
                        <x-text-input id="quota" name="quota" type="number" min="1" max="100" class="mt-1 block w-full" :value="old('quota', $posting?->quota ?? 1)" required />
                    </div>
                </div>
                <div>
                    <x-input-label for="description" value="Deskripsi *" />
                    <x-textarea-input id="description" name="description" rows="4" class="mt-1" required>{{ old('description', $posting?->description) }}</x-textarea-input>
                    <x-input-error :messages="$errors->get('description')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="requirements" value="Kualifikasi" />
                    <x-textarea-input id="requirements" name="requirements" rows="3" class="mt-1">{{ old('requirements', $posting?->requirements) }}</x-textarea-input>
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="salary_min" value="Gaji Minimum (Rp)" />
                        <x-text-input id="salary_min" name="salary_min" type="number" min="0" class="mt-1 block w-full" :value="old('salary_min', $posting?->salary_min)" />
                    </div>
                    <div>
                        <x-input-label for="salary_max" value="Gaji Maksimum (Rp)" />
                        <x-text-input id="salary_max" name="salary_max" type="number" min="0" class="mt-1 block w-full" :value="old('salary_max', $posting?->salary_max)" />
                    </div>
                </div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="show_salary" value="1" @checked((bool) old('show_salary', $posting?->show_salary)) class="rounded border-slate-300 text-emerald-600">
                    <span class="text-sm text-slate-700">Tampilkan gaji di halaman karir</span>
                </label>
            </div>
        </x-card>
        <div class="flex items-center gap-3 mt-6">
            <x-primary-button>Simpan</x-primary-button>
            <a href="{{ route('recruitment.postings.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
        </div>
    </form>
</x-app-layout>

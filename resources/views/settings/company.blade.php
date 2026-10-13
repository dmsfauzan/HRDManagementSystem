<x-app-layout>
    <x-slot name="header">Profil Perusahaan</x-slot>

    <x-page-header title="Profil Perusahaan" subtitle="Data ini tampil di halaman karir publik." />

    <form method="POST" action="{{ route('settings.company.update') }}" enctype="multipart/form-data" class="max-w-3xl">
        @csrf @method('PATCH')
        <x-card>
            <div class="space-y-4">
                <div class="flex items-center gap-4">
                    @if($company->logo_path)
                        <img src="{{ route('company.logo') }}" alt="Logo" class="w-16 h-16 rounded-lg object-contain border border-slate-200 bg-white">
                    @else
                        <span class="w-16 h-16 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </span>
                    @endif
                    <div class="flex-1">
                        <x-input-label for="logo" value="Logo Perusahaan" />
                        <input type="file" id="logo" name="logo" accept="image/*" class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:bg-slate-100 file:text-slate-700">
                        <x-input-error :messages="$errors->get('logo')" class="mt-1" />
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="name" value="Nama Perusahaan *" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $company->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="tagline" value="Tagline" />
                        <x-text-input id="tagline" name="tagline" class="mt-1 block w-full" :value="old('tagline', $company->tagline)" />
                    </div>
                    <div>
                        <x-input-label for="website" value="Website" />
                        <x-text-input id="website" name="website" class="mt-1 block w-full" :value="old('website', $company->website)" />
                        <x-input-error :messages="$errors->get('website')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" name="email" class="mt-1 block w-full" :value="old('email', $company->email)" />
                    </div>
                    <div>
                        <x-input-label for="phone" value="Telepon" />
                        <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $company->phone)" />
                    </div>
                    <div>
                        <x-input-label for="city" value="Kota" />
                        <x-text-input id="city" name="city" class="mt-1 block w-full" :value="old('city', $company->city)" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="address" value="Alamat" />
                        <x-text-input id="address" name="address" class="mt-1 block w-full" :value="old('address', $company->address)" />
                    </div>
                    <div>
                        <x-input-label for="linkedin" value="LinkedIn" />
                        <x-text-input id="linkedin" name="linkedin" class="mt-1 block w-full" :value="old('linkedin', $company->linkedin)" />
                        <x-input-error :messages="$errors->get('linkedin')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="instagram" value="Instagram" />
                        <x-text-input id="instagram" name="instagram" class="mt-1 block w-full" :value="old('instagram', $company->instagram)" />
                        <x-input-error :messages="$errors->get('instagram')" class="mt-1" />
                    </div>
                </div>

                <div>
                    <x-input-label for="about" value="Tentang Perusahaan" />
                    <x-textarea-input id="about" name="about" rows="4" class="mt-1">{{ old('about', $company->about) }}</x-textarea-input>
                </div>

                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $company->is_active)) class="rounded border-slate-300 text-emerald-600">
                    <span class="text-sm text-slate-700">Tampilkan profil perusahaan di halaman karir</span>
                </label>
            </div>
        </x-card>
        <div class="flex items-center gap-3 mt-6">
            <x-primary-button>Simpan</x-primary-button>
            <a href="{{ route('career.index') }}" target="_blank"><x-secondary-button type="button">Lihat Halaman Karir</x-secondary-button></a>
        </div>
    </form>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(auth()->user()->employee)
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Data Pribadi Karyawan</h3>
                            <p class="text-xs text-slate-500 mt-1">Perbarui nomor telepon, alamat, tanggal lahir, dan jenis kelamin Anda.</p>
                        </div>
                        <a href="{{ route('profile.employee.edit') }}"><x-secondary-button type="button">Kelola</x-secondary-button></a>
                    </div>
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <header>
                    <h2 class="text-lg font-medium text-gray-900">Foto Profil</h2>
                    <p class="mt-1 text-sm text-gray-600">Ubah foto profil Anda (privat, tidak publik).</p>
                </header>

                <div class="mt-6 flex items-center gap-4">
                    @if(auth()->user()->avatar_path)
                        <img src="{{ route('profile.avatar') }}" alt="Avatar" class="w-14 h-14 rounded-full object-cover">
                    @else
                        <span class="w-14 h-14 rounded-full bg-slate-200 flex items-center justify-center text-sm font-semibold">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
                    @endif

                    <form method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" class="flex items-center gap-2">
                        @csrf @method('PATCH')
                        <input type="file" name="avatar" accept="image/*" class="block text-sm text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:bg-slate-100 file:text-slate-700" required>
                        <x-primary-button>Unggah</x-primary-button>
                    </form>
                </div>
                <x-input-error :messages="$errors->get('avatar')" class="mt-2" />
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

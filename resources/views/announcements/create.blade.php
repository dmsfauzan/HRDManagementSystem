<x-app-layout>
    <x-slot name="header">Buat Pengumuman</x-slot>
    <x-page-header title="Buat Pengumuman" />
    <form method="POST" action="{{ route('announcements.store') }}" class="max-w-2xl">
        @csrf
        <x-card>
            <div class="space-y-4">
                <div>
                    <x-input-label for="title" value="Judul *" />
                    <x-text-input id="title" name="title" class="mt-1 block w-full" :value="old('title')" required />
                    <x-input-error :messages="$errors->get('title')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="body" value="Isi Pengumuman *" />
                    <x-textarea-input id="body" name="body" rows="6" class="mt-1" required>{{ old('body') }}</x-textarea-input>
                    <x-input-error :messages="$errors->get('body')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Aksi" />
                    <div class="flex gap-4 mt-1.5">
                        <label class="inline-flex items-center gap-2 text-sm"><input type="radio" name="action" value="draft" @checked(old('action') === 'draft') class="border-slate-300 text-emerald-600"><span>Simpan sebagai draf</span></label>
                        <label class="inline-flex items-center gap-2 text-sm"><input type="radio" name="action" value="publish" @checked(old('action', 'publish') === 'publish') class="border-slate-300 text-emerald-600"><span>Terbitkan & kirim notifikasi</span></label>
                    </div>
                </div>
            </div>
        </x-card>
        <div class="flex items-center gap-3 mt-6">
            <x-primary-button>Simpan</x-primary-button>
            <a href="{{ route('announcements.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
        </div>
    </form>
</x-app-layout>

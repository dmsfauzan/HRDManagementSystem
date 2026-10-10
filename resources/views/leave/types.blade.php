<x-app-layout>
    <x-slot name="header">Jenis Cuti</x-slot>

    <x-page-header title="Jenis Cuti" subtitle="Kelola jenis cuti dan kebijakan kuotanya." />

    <x-card title="Tambah Jenis Cuti" class="mb-6">
        <form method="POST" action="{{ route('leave.types.store') }}" class="grid grid-cols-1 sm:grid-cols-6 gap-3 items-end">
            @csrf
            <div>
                <x-input-label for="code" value="Kode" />
                <x-text-input id="code" name="code" class="mt-1 block w-full" :value="old('code')" required />
                <x-input-error :messages="$errors->get('code')" class="mt-1" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="name" value="Nama" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="default_quota" value="Kuota (hari)" />
                <x-text-input id="default_quota" name="default_quota" type="number" step="1" min="0" class="mt-1 block w-full" :value="old('default_quota', 12)" required />
            </div>
            <label class="flex items-center gap-2 pb-2"><input type="checkbox" name="is_paid" value="1" checked class="rounded border-slate-300 text-emerald-600"><span class="text-sm">Dibayar</span></label>
            <label class="flex items-center gap-2 pb-2"><input type="checkbox" name="tracks_balance" value="1" checked class="rounded border-slate-300 text-emerald-600"><span class="text-sm">Pakai saldo</span></label>
            <div class="sm:col-span-6">
                <label class="inline-flex items-center gap-2 mr-6"><input type="checkbox" name="requires_attachment" value="1" class="rounded border-slate-300 text-emerald-600"><span class="text-sm">Wajib lampiran</span></label>
                <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-emerald-600"><span class="text-sm">Aktif</span></label>
            </div>
            <div class="sm:col-span-6">
                <x-primary-button>Simpan Jenis Cuti</x-primary-button>
            </div>
        </form>
    </x-card>

    <x-card>
        @if($types->isEmpty())
            <x-empty-state title="Belum ada jenis cuti" />
        @else
            <div class="space-y-3">
                @foreach($types as $type)
                    <div x-data="{ open: false }" class="border border-slate-100 rounded-lg p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-medium text-slate-800">{{ $type->name }} <span class="text-xs text-slate-400">({{ $type->code }})</span></p>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Kuota {{ rtrim(rtrim(number_format($type->default_quota, 2, ',', '.'), '0'), ',') }} hari
                                    &middot; {{ $type->is_paid ? 'Dibayar' : 'Tidak dibayar' }}
                                    &middot; {{ $type->tracks_balance ? 'Pakai saldo' : 'Tanpa saldo' }}
                                    @if($type->requires_attachment) &middot; Wajib lampiran @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-status-badge :status="$type->is_active ? 'active' : 'inactive'" />
                                <button @click="open=!open" type="button" class="text-sm text-slate-600 hover:text-slate-900">Ubah</button>
                            </div>
                        </div>
                        <form x-show="open" x-cloak method="POST" action="{{ route('leave.types.update', $type) }}" class="grid grid-cols-1 sm:grid-cols-6 gap-3 items-end mt-4 border-t border-slate-100 pt-4">
                            @csrf @method('PUT')
                            <div>
                                <x-input-label value="Kode" />
                                <x-text-input name="code" class="mt-1 block w-full" :value="$type->code" required />
                            </div>
                            <div class="sm:col-span-2">
                                <x-input-label value="Nama" />
                                <x-text-input name="name" class="mt-1 block w-full" :value="$type->name" required />
                            </div>
                            <div>
                                <x-input-label value="Kuota" />
                                <x-text-input name="default_quota" type="number" step="1" min="0" class="mt-1 block w-full" :value="$type->default_quota" required />
                            </div>
                            <label class="flex items-center gap-2 pb-2"><input type="checkbox" name="is_paid" value="1" @checked($type->is_paid) class="rounded border-slate-300 text-emerald-600"><span class="text-sm">Dibayar</span></label>
                            <label class="flex items-center gap-2 pb-2"><input type="checkbox" name="tracks_balance" value="1" @checked($type->tracks_balance) class="rounded border-slate-300 text-emerald-600"><span class="text-sm">Pakai saldo</span></label>
                            <div class="sm:col-span-6 flex items-center gap-4">
                                <label class="inline-flex items-center gap-2"><input type="checkbox" name="requires_attachment" value="1" @checked($type->requires_attachment) class="rounded border-slate-300 text-emerald-600"><span class="text-sm">Wajib lampiran</span></label>
                                <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked($type->is_active) class="rounded border-slate-300 text-emerald-600"><span class="text-sm">Aktif</span></label>
                                <x-primary-button>Simpan</x-primary-button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
            <div class="mt-4">{{ $types->links() }}</div>
        @endif
    </x-card>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">Ajukan Cuti</x-slot>

    <x-page-header title="Ajukan Cuti" subtitle="Lengkapi formulir di bawah. Hari cuti dihitung dari hari kerja efektif." />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <form method="POST" action="{{ route('leave.store') }}" enctype="multipart/form-data" class="lg:col-span-2">
            @csrf
            <x-card title="Formulir Pengajuan">
                <div class="space-y-4">
                    <div>
                        <x-input-label for="leave_type_id" value="Jenis Cuti *" />
                        <x-select-input id="leave_type_id" name="leave_type_id" class="mt-1 block w-full" required>
                            <option value="">— Pilih jenis cuti —</option>
                            @foreach($types as $type)
                                <option value="{{ $type->id }}" @selected((string) old('leave_type_id') === (string) $type->id)>
                                    {{ $type->name }}{{ $type->requires_attachment ? ' (wajib lampiran)' : '' }}
                                </option>
                            @endforeach
                        </x-select-input>
                        <x-input-error :messages="$errors->get('leave_type_id')" class="mt-1" />
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="start_date" value="Tanggal Mulai *" />
                            <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date')" required />
                            <x-input-error :messages="$errors->get('start_date')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="end_date" value="Tanggal Selesai *" />
                            <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" :value="old('end_date')" required />
                            <x-input-error :messages="$errors->get('end_date')" class="mt-1" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="duration_type" value="Durasi *" />
                        <x-select-input id="duration_type" name="duration_type" class="mt-1 block w-full">
                            <option value="full_day" @selected(old('duration_type', 'full_day') === 'full_day')>Sehari penuh</option>
                            <option value="half_day_am" @selected(old('duration_type') === 'half_day_am')>Setengah hari (pagi)</option>
                            <option value="half_day_pm" @selected(old('duration_type') === 'half_day_pm')>Setengah hari (siang)</option>
                        </x-select-input>
                        <p class="text-xs text-slate-500 mt-1">Cuti setengah hari hanya untuk satu tanggal (mulai = selesai).</p>
                        <x-input-error :messages="$errors->get('duration_type')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="reason" value="Alasan *" />
                        <x-textarea-input id="reason" name="reason" rows="4" class="mt-1">{{ old('reason') }}</x-textarea-input>
                        <x-input-error :messages="$errors->get('reason')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="attachment" value="Lampiran (PDF/gambar, maks 5 MB)" />
                        <input type="file" id="attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp" class="mt-1 block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                        <x-input-error :messages="$errors->get('attachment')" class="mt-1" />
                    </div>
                </div>
            </x-card>
            <div class="flex items-center gap-3 mt-6">
                <x-primary-button>Kirim Pengajuan</x-primary-button>
                <a href="{{ route('leave.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
            </div>
        </form>

        <x-card title="Saldo Cuti Saya">
            <div class="space-y-3">
                @forelse($balances as $balance)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-700">{{ $balance->leaveType->name }}</span>
                        <span class="font-semibold text-slate-900">{{ rtrim(rtrim(number_format($balance->balance, 2, ',', '.'), '0'), ',') }} hari</span>
                    </div>
                @empty
                    <x-empty-state title="Belum ada saldo" description="Saldo akan dibuat otomatis saat pertama mengajukan." />
                @endforelse
            </div>
        </x-card>
    </div>
</x-app-layout>

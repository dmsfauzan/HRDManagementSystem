<x-app-layout>
    <x-slot name="header">Ajukan Koreksi Absensi</x-slot>

    <x-page-header title="Ajukan Koreksi Absensi" subtitle="Formulir ini dikirim ke atasan/HRD untuk diputuskan." />

    <form method="POST" action="{{ route('attendance.corrections.store') }}" class="max-w-2xl">
        @csrf
        <x-card>
            <div class="space-y-4">
                <div>
                    <x-input-label for="work_date" value="Tanggal Absensi *" />
                    <x-text-input id="work_date" name="work_date" type="date" class="mt-1 block w-full" :value="old('work_date')" max="{{ now()->timezone(tz())->toDateString() }}" required />
                    <x-input-error :messages="$errors->get('work_date')" class="mt-1" />
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="requested_check_in_at" value="Jam Masuk Seharusnya" />
                        <x-text-input id="requested_check_in_at" name="requested_check_in_at" type="datetime-local" class="mt-1 block w-full" :value="old('requested_check_in_at')" />
                        <x-input-error :messages="$errors->get('requested_check_in_at')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="requested_check_out_at" value="Jam Keluar Seharusnya" />
                        <x-text-input id="requested_check_out_at" name="requested_check_out_at" type="datetime-local" class="mt-1 block w-full" :value="old('requested_check_out_at')" />
                        <x-input-error :messages="$errors->get('requested_check_out_at')" class="mt-1" />
                    </div>
                </div>
                <div>
                    <x-input-label for="reason" value="Alasan *" />
                    <x-textarea-input id="reason" name="reason" rows="4" class="mt-1">{{ old('reason') }}</x-textarea-input>
                    <x-input-error :messages="$errors->get('reason')" class="mt-1" />
                </div>
            </div>
        </x-card>
        <div class="flex items-center gap-3 mt-6">
            <x-primary-button>Kirim Pengajuan</x-primary-button>
            <a href="{{ route('attendance.corrections.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
        </div>
    </form>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">Ajukan Lembur</x-slot>

    <x-page-header title="Ajukan Lembur" subtitle="Durasi minimal 30 menit, maksimal 12 jam." />

    <form method="POST" action="{{ route('overtime.store') }}" class="max-w-xl">
        @csrf
        <x-card>
            <div class="space-y-4">
                <div>
                    <x-input-label for="date" value="Tanggal *" />
                    <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date')" max="{{ now()->timezone('Asia/Jakarta')->toDateString() }}" required />
                    <x-input-error :messages="$errors->get('date')" class="mt-1" />
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="start_at" value="Jam Mulai *" />
                        <x-text-input id="start_at" name="start_at" type="time" class="mt-1 block w-full" :value="old('start_at', '17:00')" required />
                        <x-input-error :messages="$errors->get('start_at')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="end_at" value="Jam Selesai *" />
                        <x-text-input id="end_at" name="end_at" type="time" class="mt-1 block w-full" :value="old('end_at', '19:00')" required />
                        <x-input-error :messages="$errors->get('end_at')" class="mt-1" />
                    </div>
                </div>
                <div>
                    <x-input-label for="reason" value="Alasan / Pekerjaan *" />
                    <x-textarea-input id="reason" name="reason" rows="4" class="mt-1">{{ old('reason') }}</x-textarea-input>
                    <x-input-error :messages="$errors->get('reason')" class="mt-1" />
                </div>
            </div>
        </x-card>
        <div class="flex items-center gap-3 mt-6">
            <x-primary-button>Kirim Pengajuan</x-primary-button>
            <a href="{{ route('overtime.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
        </div>
    </form>
</x-app-layout>

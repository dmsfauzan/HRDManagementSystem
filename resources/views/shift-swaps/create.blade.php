<x-app-layout>
    <x-slot name="header">Ajukan Tukar Shift</x-slot>

    <x-page-header title="Ajukan Tukar Shift" subtitle="Jadwal mingguan Anda akan ditukar dengan rekan setelah disetujui." />

    <form method="POST" action="{{ route('shift-swaps.store') }}" class="max-w-xl">
        @csrf
        <x-card>
            <div class="space-y-4">
                <div>
                    <x-input-label for="target_employee_id" value="Rekan Tukar Shift *" />
                    <x-select-input id="target_employee_id" name="target_employee_id" class="mt-1 block w-full" required>
                        <option value="">— Pilih rekan —</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((string) old('target_employee_id') === (string) $employee->id)>{{ $employee->full_name }} ({{ $employee->employee_id }})</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('target_employee_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="date" value="Tanggal *" />
                    <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date')" min="{{ tz_today()->toDateString() }}" required />
                    <x-input-error :messages="$errors->get('date')" class="mt-1" />
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
            <a href="{{ route('shift-swaps.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
        </div>
    </form>
</x-app-layout>

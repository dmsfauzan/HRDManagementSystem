@php($schedule = $schedule ?? null)
@php($dayNames = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'])
@php($selected = old('work_days', $schedule?->work_days ?? [1,2,3,4,5]))

<x-card>
    <div class="space-y-4">
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="code" value="Kode *" />
                <x-text-input id="code" name="code" class="mt-1 block w-full" :value="old('code', $schedule?->code)" required />
                <x-input-error :messages="$errors->get('code')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="name" value="Nama Jadwal *" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $schedule?->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
        </div>
        <div class="grid sm:grid-cols-3 gap-4">
            <div>
                <x-input-label for="check_in_time" value="Jam Masuk *" />
                <x-text-input id="check_in_time" name="check_in_time" type="time" class="mt-1 block w-full" :value="old('check_in_time', $schedule ? substr($schedule->check_in_time, 0, 5) : '08:00')" required />
                <x-input-error :messages="$errors->get('check_in_time')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="check_out_time" value="Jam Keluar *" />
                <x-text-input id="check_out_time" name="check_out_time" type="time" class="mt-1 block w-full" :value="old('check_out_time', $schedule ? substr($schedule->check_out_time, 0, 5) : '17:00')" required />
                <x-input-error :messages="$errors->get('check_out_time')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="late_tolerance_minutes" value="Toleransi (menit) *" />
                <x-text-input id="late_tolerance_minutes" name="late_tolerance_minutes" type="number" min="0" max="180" class="mt-1 block w-full" :value="old('late_tolerance_minutes', $schedule?->late_tolerance_minutes ?? 15)" required />
                <x-input-error :messages="$errors->get('late_tolerance_minutes')" class="mt-1" />
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Hari Kerja *</label>
            <div class="flex flex-wrap gap-3 mt-1.5">
                @foreach($dayNames as $num => $label)
                    <label class="inline-flex items-center gap-1.5 text-sm">
                        <input type="checkbox" name="work_days[]" value="{{ $num }}" @checked(in_array($num, (array) $selected, true)) class="rounded border-slate-300 text-emerald-600">
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('work_days')" class="mt-1" />
        </div>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="crosses_midnight" value="1" @checked((bool) old('crosses_midnight', $schedule?->crosses_midnight)) class="rounded border-slate-300 text-emerald-600">
            <span class="text-sm text-slate-700">Shift lintas hari (mis. malam)</span>
        </label>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $schedule?->is_active ?? true)) class="rounded border-slate-300 text-emerald-600">
            <span class="text-sm text-slate-700">Aktif</span>
        </label>
    </div>
</x-card>

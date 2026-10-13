@php($employee = $employee ?? null)
@php($isEdit = ! is_null($employee))

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <x-card title="Data Karyawan">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="employee_id" value="Employee ID *" />
                    <x-text-input id="employee_id" name="employee_id" type="text" class="mt-1 block w-full"
                        :value="old('employee_id', $employee?->employee_id)" placeholder="cth: IT-003" required />
                    <x-input-error :messages="$errors->get('employee_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="full_name" value="Nama Lengkap *" />
                    <x-text-input id="full_name" name="full_name" type="text" class="mt-1 block w-full"
                        :value="old('full_name', $employee?->full_name)" required />
                    <x-input-error :messages="$errors->get('full_name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                        :value="old('email', $employee?->email)" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="phone" value="Nomor Telepon" />
                    <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full"
                        :value="old('phone', $employee?->phone)" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="birth_date" value="Tanggal Lahir" />
                    <x-text-input id="birth_date" name="birth_date" type="date" class="mt-1 block w-full"
                        :value="old('birth_date', $employee?->birth_date?->format('Y-m-d'))" />
                    <x-input-error :messages="$errors->get('birth_date')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="gender" value="Jenis Kelamin" />
                    <x-select-input id="gender" name="gender" class="mt-1 block w-full">
                        <option value="">— Pilih —</option>
                        <option value="male" @selected(old('gender', $employee?->gender) === 'male')>Laki-laki</option>
                        <option value="female" @selected(old('gender', $employee?->gender) === 'female')>Perempuan</option>
                    </x-select-input>
                    <x-input-error :messages="$errors->get('gender')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="address" value="Alamat" />
                    <x-textarea-input id="address" name="address" rows="2" class="mt-1">{{ old('address', $employee?->address) }}</x-textarea-input>
                    <x-input-error :messages="$errors->get('address')" class="mt-1" />
                </div>
            </div>
        </x-card>

        <x-card title="Kepegawaian">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="department_id" value="Departemen" />
                    <x-select-input id="department_id" name="department_id" class="mt-1 block w-full">
                        <option value="">— Pilih —</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected((int) old('department_id', $employee?->department_id) === $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('department_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="position_id" value="Jabatan" />
                    <x-select-input id="position_id" name="position_id" class="mt-1 block w-full">
                        <option value="">— Pilih —</option>
                        @foreach($positions as $position)
                            <option value="{{ $position->id }}" @selected((int) old('position_id', $employee?->position_id) === $position->id)>{{ $position->name }} @if($position->department) ({{ $position->department->name }}) @endif</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('position_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="manager_id" value="Atasan Langsung" />
                    <x-select-input id="manager_id" name="manager_id" class="mt-1 block w-full">
                        <option value="">— Tidak ada —</option>
                        @foreach($managers as $manager)
                            @continue($employee && $manager->id === $employee->id)
                            <option value="{{ $manager->id }}" @selected((int) old('manager_id', $employee?->manager_id) === $manager->id)>{{ $manager->full_name }} ({{ $manager->employee_id }})</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('manager_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="work_schedule_id" value="Jadwal Kerja" />
                    <x-select-input id="work_schedule_id" name="work_schedule_id" class="mt-1 block w-full">
                        <option value="">— Pilih —</option>
                        @foreach($schedules as $schedule)
                            <option value="{{ $schedule->id }}" @selected((int) old('work_schedule_id', $employee?->work_schedule_id) === $schedule->id)>{{ $schedule->name }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('work_schedule_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="join_date" value="Tanggal Mulai Bekerja *" />
                    <x-text-input id="join_date" name="join_date" type="date" class="mt-1 block w-full"
                        :value="old('join_date', $employee?->join_date?->format('Y-m-d'))" required />
                    <x-input-error :messages="$errors->get('join_date')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="contract_end_date" value="Tanggal Berakhir Kontrak" />
                    <x-text-input id="contract_end_date" name="contract_end_date" type="date" class="mt-1 block w-full"
                        :value="old('contract_end_date', $employee?->contract_end_date?->format('Y-m-d'))" />
                    <x-input-error :messages="$errors->get('contract_end_date')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="employment_type" value="Tipe Kepegawaian *" />
                    <x-select-input id="employment_type" name="employment_type" class="mt-1 block w-full">
                        @foreach(['permanent' => 'Tetap', 'contract' => 'Kontrak', 'intern' => 'Magang', 'probation' => 'Percobaan'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('employment_type', $employee?->employment_type ?? 'permanent') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('employment_type')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="employment_status" value="Status Kepegawaian *" />
                    <x-select-input id="employment_status" name="employment_status" class="mt-1 block w-full">
                        @foreach(['active' => 'Aktif', 'inactive' => 'Nonaktif', 'resigned' => 'Resign'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('employment_status', $employee?->employment_status ?? 'active') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('employment_status')" class="mt-1" />
                </div>
            </div>
        </x-card>
    </div>

    <div class="space-y-6">
        <x-card title="Foto Profil">
            @if($employee?->photo_path)
                <img src="{{ route('employees.photo', $employee) }}" alt="Foto {{ $employee->full_name }}" class="w-24 h-24 rounded-full object-cover mb-4">
            @else
                <div class="w-24 h-24 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-4">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
            @endif
            <input type="file" name="photo" accept="image/*" class="block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
            <p class="mt-2 text-xs text-slate-500">JPG, PNG, atau WEBP. Maks 2 MB.</p>
            <x-input-error :messages="$errors->get('photo')" class="mt-1" />
        </x-card>

        @unless($isEdit)
            <x-card title="Akun Login (opsional)">
                <label class="flex items-start gap-2 cursor-pointer">
                    <input type="checkbox" name="create_account" value="1" x-data @checked(old('create_account')) @change="$dispatch('toggle-account', { open: $event.target.checked })"
                        class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-slate-700">Buat akun login untuk karyawan ini</span>
                </label>
                <div x-data="{ open: {{ old('create_account') ? 'true' : 'false' }} }" @toggle-account.window="open = $event.detail.open" x-show="open" x-cloak class="mt-4 space-y-4">
                    <div>
                        <x-input-label for="account_email" value="Email Akun *" />
                        <x-text-input id="account_email" name="account_email" type="email" class="mt-1 block w-full" :value="old('account_email')" />
                        <x-input-error :messages="$errors->get('account_email')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="account_password" value="Password *" />
                        <x-text-input id="account_password" name="account_password" type="text" class="mt-1 block w-full" :value="old('account_password')" />
                        <x-input-error :messages="$errors->get('account_password')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="account_role" value="Role *" />
                        <x-select-input id="account_role" name="account_role" class="mt-1 block w-full">
                            <option value="karyawan" @selected(old('account_role') === 'karyawan')>Karyawan</option>
                            <option value="manager" @selected(old('account_role') === 'manager')>Manager</option>
                        </x-select-input>
                        <x-input-error :messages="$errors->get('account_role')" class="mt-1" />
                    </div>
                </div>
            </x-card>
        @endunless
    </div>
</div>

<div class="flex items-center gap-3 mt-6">
    <x-primary-button>{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Karyawan' }}</x-primary-button>
    <a href="{{ $isEdit ? route('employees.show', $employee) : route('employees.index') }}">
        <x-secondary-button type="button">Batal</x-secondary-button>
    </a>
</div>

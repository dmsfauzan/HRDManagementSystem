<x-app-layout>
    <x-slot name="header">Saldo Cuti</x-slot>

    @php($canManage = auth()->user()->can('leave.balances_manage'))

    <x-page-header title="Saldo Cuti" :subtitle="$canManage ? 'Kelola saldo cuti seluruh karyawan.' : 'Saldo cuti Anda.'" />

    @if($canManage)
        <x-card title="Tetapkan Saldo" class="mb-6">
            <form method="POST" action="{{ route('leave.balances.store') }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
                @csrf
                <div class="sm:col-span-2">
                    <x-input-label for="employee_id" value="Karyawan" />
                    <x-select-input id="employee_id" name="employee_id" class="mt-1 block w-full" required>
                        <option value="">— Pilih karyawan —</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((string) old('employee_id') === (string) $employee->id)>{{ $employee->full_name }} ({{ $employee->employee_id }})</option>
                        @endforeach
                    </x-select-input>
                </div>
                <div>
                    <x-input-label for="leave_type_id" value="Jenis" />
                    <x-select-input id="leave_type_id" name="leave_type_id" class="mt-1 block w-full" required>
                        <option value="">— Pilih —</option>
                        @foreach($leaveTypes as $type)
                            <option value="{{ $type->id }}" @selected((string) old('leave_type_id') === (string) $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <div>
                    <x-input-label for="year" value="Tahun" />
                    <x-text-input id="year" name="year" type="number" min="2000" max="2100" class="mt-1 block w-full" :value="old('year', $year)" required />
                </div>
                <div>
                    <x-input-label for="balance" value="Saldo (hari)" />
                    <x-text-input id="balance" name="balance" type="number" step="0.5" min="0" class="mt-1 block w-full" :value="old('balance')" required />
                </div>
                <div class="sm:col-span-5">
                    <x-primary-button>Simpan Saldo</x-primary-button>
                </div>
            </form>
        </x-card>
    @endif

    <x-card>
        <form method="GET" action="{{ route('leave.balances.index') }}" class="flex flex-wrap gap-2 mb-4">
            <x-text-input name="year" type="number" min="2000" max="2100" :value="request('year', $year)" class="w-28" />
            @if($canManage)
                <x-text-input name="search" :value="request('search')" placeholder="Cari karyawan..." class="flex-1 min-w-[180px]" />
            @endif
            <x-select-input name="leave_type_id" onchange="this.form.submit()">
                <option value="">Semua jenis</option>
                @foreach($leaveTypes as $type)
                    <option value="{{ $type->id }}" @selected((string) request('leave_type_id') === (string) $type->id)>{{ $type->name }}</option>
                @endforeach
            </x-select-input>
            <x-secondary-button type="submit">Terapkan</x-secondary-button>
        </form>

        @if($balances->isEmpty())
            <x-empty-state title="Belum ada data saldo cuti" />
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                            <th class="py-2 px-2 font-medium">Karyawan</th>
                            <th class="py-2 pr-4 font-medium">Jenis</th>
                            <th class="py-2 pr-4 font-medium">Tahun</th>
                            <th class="py-2 pr-4 font-medium">Terpakai</th>
                            <th class="py-2 pr-4 font-medium">Sisa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($balances as $balance)
                            <tr class="hover:bg-slate-50/60">
                                <td class="py-3 px-2">
                                    <span class="block font-medium text-slate-800">{{ $balance->employee->full_name ?? '—' }}</span>
                                    <span class="block text-xs text-slate-500">{{ $balance->employee->department->name ?? '—' }}</span>
                                </td>
                                <td class="py-3 pr-4 text-slate-700">{{ $balance->leaveType->name ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $balance->year }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ rtrim(rtrim(number_format($balance->used, 2, ',', '.'), '0'), ',') }}</td>
                                <td class="py-3 pr-4 font-semibold text-slate-900">{{ rtrim(rtrim(number_format($balance->balance, 2, ',', '.'), '0'), ',') }} hari</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $balances->links() }}</div>
        @endif
    </x-card>
</x-app-layout>

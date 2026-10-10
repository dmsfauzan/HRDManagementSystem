<x-app-layout>
    <x-slot name="header">Karyawan</x-slot>

    <x-page-header title="Karyawan" subtitle="Kelola data karyawan.">
        <x-slot:actions>
            <a href="{{ route('employees.create') }}">
                <x-primary-button>Tambah Karyawan</x-primary-button>
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <form method="GET" action="{{ route('employees.index') }}" class="flex flex-wrap gap-2 mb-4">
            <x-text-input name="search" :value="request('search')" placeholder="Cari nama, NIP, atau email..." class="flex-1 min-w-[200px]" />
            <x-select-input name="department_id" onchange="this.form.submit()">
                <option value="">Semua departemen</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                @endforeach
            </x-select-input>
            <x-select-input name="status" onchange="this.form.submit()">
                <option value="">Semua status</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                <option value="resigned" @selected(request('status') === 'resigned')>Resign</option>
            </x-select-input>
            <x-secondary-button type="submit">Cari</x-secondary-button>
            @if(request()->hasAny(['search', 'department_id', 'status', 'type']))
                <a href="{{ route('employees.index') }}" class="text-sm text-slate-600 hover:text-slate-900 underline">Reset</a>
            @endif
        </form>

        @if($employees->isEmpty())
            <x-empty-state title="Belum ada karyawan yang sesuai" description="Sesuaikan filter atau tambah karyawan baru." />
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                            <th class="py-2 px-2 font-medium">Karyawan</th>
                            <th class="py-2 pr-4 font-medium">Department / Jabatan</th>
                            <th class="py-2 pr-4 font-medium">Atasan</th>
                            <th class="py-2 pr-4 font-medium">Tipe</th>
                            <th class="py-2 pr-4 font-medium">Status</th>
                            <th class="py-2 pr-4 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($employees as $employee)
                            <tr class="hover:bg-slate-50/60">
                                <td class="py-3 px-2">
                                    <a href="{{ route('employees.show', $employee) }}" class="flex items-center gap-3 group">
                                        @if($employee->photo_path)
                                            <img src="{{ route('employees.photo', $employee) }}" alt="" class="w-8 h-8 rounded-full object-cover shrink-0">
                                        @else
                                            <span class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-xs font-semibold text-slate-500 shrink-0">{{ strtoupper(substr($employee->full_name, 0, 1)) }}</span>
                                        @endif
                                        <span>
                                            <span class="block text-slate-900 group-hover:text-emerald-700 font-medium">{{ $employee->full_name }}</span>
                                            <span class="block text-xs text-slate-500">{{ $employee->employee_id }}</span>
                                        </span>
                                    </a>
                                </td>
                                <td class="py-3 pr-4">
                                    <span class="block text-slate-700">{{ $employee->position->name ?? '—' }}</span>
                                    <span class="block text-xs text-slate-500">{{ $employee->department->name ?? '—' }}</span>
                                </td>
                                <td class="py-3 pr-4 text-slate-600">{{ $employee->manager->full_name ?? '—' }}</td>
                                <td class="py-3 pr-4"><x-status-badge :status="$employee->employment_type" /></td>
                                <td class="py-3 pr-4"><x-status-badge :status="$employee->employment_status" /></td>
                                <td class="py-3 pr-4 text-right">
                                    <a href="{{ route('employees.show', $employee) }}" class="text-sm text-slate-600 hover:text-slate-900">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $employees->links() }}
            </div>
        @endif
    </x-card>
</x-app-layout>

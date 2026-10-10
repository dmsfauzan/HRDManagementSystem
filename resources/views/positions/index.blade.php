<x-app-layout>
    <x-slot name="header">Jabatan</x-slot>

    <x-page-header title="Jabatan" subtitle="Kelola jabatan dan kaitannya dengan departemen.">
        <x-slot:actions>
            @can('create', \App\Models\Position::class)
                <a href="{{ route('positions.create') }}"><x-primary-button>Tambah Jabatan</x-primary-button></a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <form method="GET" action="{{ route('positions.index') }}" class="flex flex-wrap gap-2 mb-4">
            <x-text-input name="search" :value="request('search')" placeholder="Cari nama atau kode..." class="flex-1 min-w-[200px]" />
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
            </x-select-input>
            <x-secondary-button type="submit">Cari</x-secondary-button>
        </form>

        @if($positions->isEmpty())
            <x-empty-state title="Belum ada jabatan" />
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                            <th class="py-2 px-2 font-medium">Kode</th>
                            <th class="py-2 pr-4 font-medium">Nama Jabatan</th>
                            <th class="py-2 pr-4 font-medium">Departemen</th>
                            <th class="py-2 pr-4 font-medium">Level</th>
                            <th class="py-2 pr-4 font-medium">Karyawan</th>
                            <th class="py-2 pr-4 font-medium">Status</th>
                            <th class="py-2 pr-4 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($positions as $position)
                            <tr class="hover:bg-slate-50/60">
                                <td class="py-3 px-2 font-mono text-xs text-slate-600">{{ $position->code }}</td>
                                <td class="py-3 pr-4 font-medium text-slate-900">{{ $position->name }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $position->department->name ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $position->level ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $position->employees_count }}</td>
                                <td class="py-3 pr-4"><x-status-badge :status="$position->is_active ? 'active' : 'inactive'" /></td>
                                <td class="py-3 pr-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('positions.edit', $position) }}" class="text-sm text-slate-600 hover:text-slate-900">Ubah</a>
                                        @can('delete', $position)
                                            <form method="POST" action="{{ route('positions.destroy', $position) }}" onsubmit="return confirm('Hapus jabatan ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-sm text-red-600 hover:text-red-800">Hapus</button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $positions->links() }}</div>
        @endif
    </x-card>
</x-app-layout>

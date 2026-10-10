<x-app-layout>
    <x-slot name="header">Departemen</x-slot>

    <x-page-header title="Departemen" subtitle="Kelola struktur departemen perusahaan.">
        <x-slot:actions>
            @can('create', \App\Models\Department::class)
                <a href="{{ route('departments.create') }}"><x-primary-button>Tambah Departemen</x-primary-button></a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <form method="GET" action="{{ route('departments.index') }}" class="flex flex-wrap gap-2 mb-4">
            <x-text-input name="search" :value="request('search')" placeholder="Cari nama atau kode..." class="flex-1 min-w-[200px]" />
            <x-select-input name="status" onchange="this.form.submit()">
                <option value="">Semua status</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
            </x-select-input>
            <x-secondary-button type="submit">Cari</x-secondary-button>
        </form>

        @if($departments->isEmpty())
            <x-empty-state title="Belum ada departemen" />
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                            <th class="py-2 px-2 font-medium">Kode</th>
                            <th class="py-2 pr-4 font-medium">Nama</th>
                            <th class="py-2 pr-4 font-medium">Jumlah Karyawan</th>
                            <th class="py-2 pr-4 font-medium">Status</th>
                            <th class="py-2 pr-4 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($departments as $department)
                            <tr class="hover:bg-slate-50/60">
                                <td class="py-3 px-2 font-mono text-xs text-slate-600">{{ $department->code }}</td>
                                <td class="py-3 pr-4">
                                    <a href="{{ route('departments.show', $department) }}" class="font-medium text-slate-900 hover:text-emerald-700">{{ $department->name }}</a>
                                </td>
                                <td class="py-3 pr-4 text-slate-600">{{ $department->employees_count }}</td>
                                <td class="py-3 pr-4"><x-status-badge :status="$department->is_active ? 'active' : 'inactive'" /></td>
                                <td class="py-3 pr-4 text-right space-x-3">
                                    <a href="{{ route('departments.edit', $department) }}" class="text-sm text-slate-600 hover:text-slate-900">Ubah</a>
                                    @can('delete', $department)
                                        <form method="POST" action="{{ route('departments.destroy', $department) }}" onsubmit="return confirm('Hapus departemen ini?')" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-sm text-red-600 hover:text-red-800">Hapus</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $departments->links() }}</div>
        @endif
    </x-card>
</x-app-layout>

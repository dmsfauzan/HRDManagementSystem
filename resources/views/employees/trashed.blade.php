<x-app-layout>
    <x-slot name="header">Karyawan Terhapus</x-slot>

    <x-page-header title="Arsip Karyawan" subtitle="Data karyawan yang dihapus (soft delete) dapat dipulihkan.">
        <x-slot:actions>
            <a href="{{ route('employees.index') }}"><x-secondary-button>Kembali</x-secondary-button></a>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        @if($employees->isEmpty())
            <x-empty-state title="Tidak ada karyawan terhapus" />
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                            <th class="py-2 px-2 font-medium">Nama</th>
                            <th class="py-2 pr-4 font-medium">ID</th>
                            <th class="py-2 pr-4 font-medium">Departemen</th>
                            <th class="py-2 pr-4 font-medium">Dihapus</th>
                            <th class="py-2 pr-4 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($employees as $employee)
                            <tr>
                                <td class="py-3 px-2 font-medium text-slate-800">{{ $employee->full_name }}</td>
                                <td class="py-3 pr-4 font-mono text-xs text-slate-600">{{ $employee->employee_id }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $employee->department->name ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-600 text-xs">{{ $employee->deleted_at?->translatedFormat('d M Y H:i') }}</td>
                                <td class="py-3 pr-4 text-right">
                                    <form method="POST" action="{{ route('employees.restore', $employee->id) }}">@csrf @method('PATCH')
                                        <button type="submit" class="text-sm text-emerald-700 hover:underline">Pulihkan</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $employees->links() }}</div>
        @endif
    </x-card>
</x-app-layout>

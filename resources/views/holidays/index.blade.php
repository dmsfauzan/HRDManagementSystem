<x-app-layout>
    <x-slot name="header">Hari Libur</x-slot>

    <x-page-header title="Hari Libur" subtitle="Kelola hari libur nasional/cuti bersama yang memengaruhi perhitungan hari kerja.">
        <x-slot:actions>
            <a href="{{ route('holidays.create') }}"><x-primary-button>Tambah Hari Libur</x-primary-button></a>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        @if($holidays->isEmpty())
            <x-empty-state title="Belum ada hari libur" />
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                            <th class="py-2 px-2 font-medium">Tanggal</th>
                            <th class="py-2 pr-4 font-medium">Hari</th>
                            <th class="py-2 pr-4 font-medium">Nama</th>
                            <th class="py-2 pr-4 font-medium">Jenis</th>
                            <th class="py-2 pr-4 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($holidays as $holiday)
                            <tr class="hover:bg-slate-50/60">
                                <td class="py-3 px-2 font-medium text-slate-800">{{ $holiday->date->format('d/m/Y') }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $holiday->date->translatedFormat('l') }}</td>
                                <td class="py-3 pr-4 text-slate-700">{{ $holiday->name }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $holiday->is_national ? 'Nasional' : 'Cuti Bersama' }}</td>
                                <td class="py-3 pr-4 text-right space-x-3">
                                    <a href="{{ route('holidays.edit', $holiday) }}" class="text-sm text-slate-600 hover:text-slate-900">Ubah</a>
                                    <form method="POST" action="{{ route('holidays.destroy', $holiday) }}" onsubmit="return confirm('Hapus hari libur ini?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $holidays->links() }}</div>
        @endif
    </x-card>
</x-app-layout>

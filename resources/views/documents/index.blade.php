<x-app-layout>
    <x-slot name="header">Dokumen HR</x-slot>

    <x-page-header title="Dokumen HR" subtitle="Dokumen kepegawaian tersimpan aman (privat)." />

    @if($canManage)
        <x-card title="Unggah Dokumen" class="mb-6">
            <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                @csrf
                <div>
                    <x-input-label for="employee_id" value="Karyawan *" />
                    <x-select-input id="employee_id" name="employee_id" class="mt-1 block w-full" required>
                        <option value="">— Pilih —</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((string) old('employee_id') === (string) $employee->id)>{{ $employee->full_name }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <div>
                    <x-input-label for="title" value="Judul *" />
                    <x-text-input id="title" name="title" class="mt-1 block w-full" :value="old('title')" required />
                </div>
                <div>
                    <x-input-label for="category" value="Kategori" />
                    <x-text-input id="category" name="category" class="mt-1 block w-full" :value="old('category')" placeholder="Kontrak, KTP, ..." />
                </div>
                <div>
                    <x-input-label for="file" value="Berkas *" />
                    <input type="file" id="file" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:bg-slate-100 file:text-slate-700" required>
                </div>
                <div class="sm:col-span-4">
                    <x-input-error :messages="$errors->all()" class="mb-2" />
                    <x-primary-button>Unggah</x-primary-button>
                </div>
            </form>
        </x-card>
    @endif

    <x-card>
        @if($canManage)
            <form method="GET" action="{{ route('documents.index') }}" class="mb-4">
                <x-select-input name="employee_id" onchange="this.form.submit()">
                    <option value="">Semua karyawan</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" @selected((string) request('employee_id') === (string) $employee->id)>{{ $employee->full_name }}</option>
                    @endforeach
                </x-select-input>
            </form>
        @endif

        @if($documents->isEmpty())
            <x-empty-state title="Belum ada dokumen" />
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                            <th class="py-2 px-2 font-medium">Judul</th>
                            <th class="py-2 pr-4 font-medium">Karyawan</th>
                            <th class="py-2 pr-4 font-medium">Kategori</th>
                            <th class="py-2 pr-4 font-medium">Diunggah</th>
                            <th class="py-2 pr-4 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($documents as $document)
                            <tr class="hover:bg-slate-50/60">
                                <td class="py-3 px-2 font-medium text-slate-800">{{ $document->title }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $document->employee->full_name ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $document->category ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-600 text-xs">{{ $document->created_at->translatedFormat('d M Y') }}</td>
                                <td class="py-3 pr-4 text-right space-x-3">
                                    <a href="{{ route('documents.download', $document) }}" class="text-sm text-emerald-700 hover:underline">Unduh</a>
                                    @if($canManage)
                                        <form method="POST" action="{{ route('documents.destroy', $document) }}" class="inline">@csrf @method('DELETE')
                                            <x-confirm-modal title="Hapus Dokumen" message="Dokumen ini akan dihapus permanen dari penyimpanan." confirm-label="Hapus">
                                                <x-slot:trigger><button type="button" class="text-sm text-red-600 hover:text-red-800">Hapus</button></x-slot:trigger>
                                                <x-slot:action><button type="submit" class="px-4 py-2 text-sm font-medium rounded-lg bg-red-600 text-white hover:bg-red-700">Hapus</button></x-slot:action>
                                            </x-confirm-modal>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $documents->links() }}</div>
        @endif
    </x-card>
</x-app-layout>

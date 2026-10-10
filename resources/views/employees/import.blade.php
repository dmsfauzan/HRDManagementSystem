<x-app-layout>
    <x-slot name="header">Impor Karyawan</x-slot>

    <x-page-header title="Impor Karyawan dari CSV" subtitle="Unggah berkas CSV mengikuti template." />

    <x-card class="mb-6" title="Langkah">
        <ol class="text-sm text-slate-600 list-decimal list-inside space-y-1">
            <li>Unduh <a href="{{ route('employees.import.template') }}" class="text-emerald-700 hover:underline">template CSV</a>.</li>
            <li>Kolom wajib: <code class="font-mono text-xs bg-slate-100 px-1 rounded">employee_id, full_name, join_date</code>.</li>
            <li>Kolom opsional: <code class="font-mono text-xs bg-slate-100 px-1 rounded">email, phone, department_code, position_code, manager_employee_id, employment_type, employment_status</code>.</li>
            <li>Unggah file dan tinjau hasil: baris valid diimpor, baris bermasalah dilaporkan.</li>
        </ol>
    </x-card>

    <form method="POST" action="{{ route('employees.import.store') }}" enctype="multipart/form-data" class="max-w-xl">
        @csrf
        <x-card>
            <x-input-label for="file" value="Berkas CSV (maks 2 MB) *" />
            <input type="file" id="file" name="file" accept=".csv,.txt" class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:bg-slate-100 file:text-slate-700" required>
            <x-input-error :messages="$errors->get('file')" class="mt-1" />
        </x-card>
        <div class="flex items-center gap-3 mt-6">
            <x-primary-button>Impor</x-primary-button>
            <a href="{{ route('employees.index') }}"><x-secondary-button type="button">Kembali</x-secondary-button></a>
        </div>
    </form>

    @if(session('import_result'))
        @php($result = session('import_result'))
        <x-card title="Hasil Impor" class="mt-6">
            <p class="text-sm text-slate-700"><span class="font-semibold text-emerald-700">{{ $result['imported'] }}</span> baris berhasil diimpor @if(count($result['errors'])) &middot; <span class="font-semibold text-red-600">{{ count($result['errors']) }}</span> baris gagal @endif</p>
            @if(count($result['errors']))
                <ul class="mt-3 text-xs text-slate-600 space-y-1 list-disc list-inside">
                    @foreach($result['errors'] as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    @endif
</x-app-layout>

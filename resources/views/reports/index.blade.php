<x-app-layout>
    <x-slot name="header">Laporan</x-slot>

    <x-page-header title="Laporan HR" subtitle="Akses data dan ekspor sesuai kewenangan Anda." />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <x-card title="Ringkasan">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Karyawan aktif</dt><dd class="font-semibold text-slate-900">{{ $stats['employees'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Departemen</dt><dd class="font-semibold text-slate-900">{{ $stats['departments'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Absensi bulan ini</dt><dd class="font-semibold text-slate-900">{{ $stats['attendance_this_month'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Pengajuan cuti tahun ini</dt><dd class="font-semibold text-slate-900">{{ $stats['leaves_this_year'] }}</dd></div>
            </dl>
        </x-card>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <a href="{{ route('reports.employees') }}" class="bg-white rounded-xl border border-slate-200 p-5 hover:border-emerald-300 hover:bg-emerald-50/50 transition">
            <p class="text-sm font-semibold text-slate-900">Data Karyawan</p>
            <p class="text-xs text-slate-500 mt-1">Daftar karyawan & jumlah per departemen.</p>
        </a>
        <a href="{{ route('reports.attendance') }}" class="bg-white rounded-xl border border-slate-200 p-5 hover:border-emerald-300 hover:bg-emerald-50/50 transition">
            <p class="text-sm font-semibold text-slate-900">Rekap Absensi</p>
            <p class="text-xs text-slate-500 mt-1">Kehadiran, keterlambatan, izin.</p>
        </a>
        <a href="{{ route('reports.leaves') }}" class="bg-white rounded-xl border border-slate-200 p-5 hover:border-emerald-300 hover:bg-emerald-50/50 transition">
            <p class="text-sm font-semibold text-slate-900">Rekap Cuti</p>
            <p class="text-xs text-slate-500 mt-1">Daftar pengajuan per status.</p>
        </a>
        <a href="{{ route('reports.overtime') }}" class="bg-white rounded-xl border border-slate-200 p-5 hover:border-emerald-300 hover:bg-emerald-50/50 transition">
            <p class="text-sm font-semibold text-slate-900">Rekap Lembur</p>
            <p class="text-xs text-slate-500 mt-1">Total lembur disetujui per periode.</p>
        </a>
        <a href="{{ route('reports.recruitment') }}" class="bg-white rounded-xl border border-slate-200 p-5 hover:border-emerald-300 hover:bg-emerald-50/50 transition">
            <p class="text-sm font-semibold text-slate-900">Laporan Rekrutmen</p>
            <p class="text-xs text-slate-500 mt-1">Funnel, time-to-hire, sumber pelamar.</p>
        </a>
    </div>
</x-app-layout>

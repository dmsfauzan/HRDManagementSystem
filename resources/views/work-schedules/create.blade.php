<x-app-layout>
    <x-slot name="header">Tambah Jadwal</x-slot>
    <x-page-header title="Tambah Jadwal Kerja" />
    <form method="POST" action="{{ route('work-schedules.store') }}" class="max-w-2xl">
        @csrf
        @include('work-schedules._form')
        <div class="flex items-center gap-3 mt-6">
            <x-primary-button>Simpan</x-primary-button>
            <a href="{{ route('work-schedules.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
        </div>
    </form>
</x-app-layout>

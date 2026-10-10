<x-app-layout>
    <x-slot name="header">Ubah Jadwal</x-slot>
    <x-page-header title="Ubah Jadwal Kerja" />
    <form method="POST" action="{{ route('work-schedules.update', $schedule) }}" class="max-w-2xl">
        @csrf @method('PUT')
        @include('work-schedules._form')
        <div class="flex items-center gap-3 mt-6">
            <x-primary-button>Simpan Perubahan</x-primary-button>
            <a href="{{ route('work-schedules.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
        </div>
    </form>
</x-app-layout>

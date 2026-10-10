<x-app-layout>
    <x-slot name="header">Karyawan</x-slot>

    <x-page-header title="Tambah Karyawan" subtitle="Lengkapi data karyawan dan akun login bila diperlukan." />

    <form method="POST" action="{{ route('employees.store') }}" enctype="multipart/form-data">
        @csrf
        @include('employees._form')
    </form>
</x-app-layout>

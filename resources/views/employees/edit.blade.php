<x-app-layout>
    <x-slot name="header">Karyawan</x-slot>

    <x-page-header title="Ubah Data Karyawan" :subtitle="$employee->full_name . ' — ' . $employee->employee_id" />

    <form method="POST" action="{{ route('employees.update', $employee) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('employees._form')
    </form>
</x-app-layout>

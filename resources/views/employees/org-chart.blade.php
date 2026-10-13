<x-app-layout>
    <x-slot name="header">Struktur Organisasi</x-slot>

    <x-page-header title="Struktur Organisasi" subtitle="Hierarki berdasarkan atasan langsung." />

    <x-card>
        @if($roots->isEmpty())
            <x-empty-state title="Belum ada data struktur" description="Tetapkan atasan langsung pada data karyawan untuk membentuk struktur." />
        @else
            <ul>
                @foreach($roots as $root)
                    <x-org-node :employee="$root" :by-manager="$byManager" />
                @endforeach
            </ul>
        @endif
    </x-card>
</x-app-layout>

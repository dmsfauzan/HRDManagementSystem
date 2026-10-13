<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Karier' }} — {{ config('app.name', 'HRIS') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 font-sans antialiased">
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="{{ route('career.index') }}" class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold text-sm">HR</span>
                <span>
                    <span class="block text-sm font-semibold leading-none text-slate-900">Karier</span>
                    <span class="block text-[11px] text-slate-500">HRIS — HRD Management</span>
                </span>
            </a>
            <a href="{{ route('career.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Semua Lowongan</a>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
        {{ $slot }}
    </main>

    <footer class="max-w-5xl mx-auto px-4 sm:px-6 py-8 text-xs text-slate-400">
        &copy; {{ date('Y') }} HRIS. Semua data pelamar disimpan secara privat.
    </footer>
</body>
</html>

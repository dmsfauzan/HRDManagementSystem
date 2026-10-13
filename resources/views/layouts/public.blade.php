<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Karier' }} — {{ $company->name ?? config('app.name', 'HRIS') }}</title>

    @isset($meta)
        {{ $meta }}
    @else
        <meta name="description" content="{{ $company->tagline ?? 'Lowongan kerja terbaru' }}">
        <meta property="og:title" content="{{ $title ?? 'Karier' }}">
        <meta property="og:type" content="website">
    @endisset

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 font-sans antialiased">
    <header class="bg-white border-b border-slate-200 sticky top-0 z-20">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="{{ route('career.index') }}" class="flex items-center gap-3">
                @if(($company ?? null)?->logo_path)
                    <img src="{{ route('company.logo') }}" alt="Logo" class="w-9 h-9 rounded-lg object-contain border border-slate-200">
                @else
                    <span class="w-9 h-9 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold text-sm">{{ strtoupper(substr($company->name ?? 'HR', 0, 2)) }}</span>
                @endif
                <span>
                    <span class="block text-sm font-semibold leading-none text-slate-900">{{ $company->name ?? config('app.name') }}</span>
                    <span class="block text-[11px] text-slate-500">{{ $company->tagline ?? 'Karier' }}</span>
                </span>
            </a>
            <a href="{{ route('career.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Semua Lowongan</a>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
        {{ $slot }}
    </main>

    <footer class="max-w-5xl mx-auto px-4 sm:px-6 py-8 text-xs text-slate-400">
        &copy; {{ date('Y') }} {{ $company->name ?? config('app.name') }}.
        @if(($company ?? null)?->website) <a href="{{ $company->website }}" class="hover:underline">{{ $company->website }}</a> @endif
        &middot; Data pelamar disimpan secara privat.
    </footer>
</body>
</html>

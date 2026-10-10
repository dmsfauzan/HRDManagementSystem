<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} — {{ config('app.name', 'HRIS') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen flex items-center justify-center bg-slate-50 text-slate-800 font-sans antialiased p-6">
    <div class="max-w-md w-full text-center">
        <div class="flex items-center justify-center gap-2 mb-6">
            <div class="w-10 h-10 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold text-sm">HR</div>
            <span class="text-sm font-semibold tracking-tight">HRIS</span>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-8">
            <p class="text-6xl font-black tracking-tight text-slate-900">{{ $code }}</p>
            <h1 class="mt-3 text-lg font-semibold text-slate-900">{{ $title }}</h1>
            <p class="mt-2 text-sm text-slate-500 leading-relaxed">{{ $message }}</p>

            <div class="mt-6 flex items-center justify-center gap-3">
                <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Kembali ke Aplikasi</a>
                <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-white border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50">Kembali</a>
            </div>
        </div>

        <p class="mt-6 text-xs text-slate-400">Jika masalah berlanjut, hubungi administrator.</p>
    </div>
</body>
</html>

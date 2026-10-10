@props([
    'title' => 'Konfirmasi',
    'message' => 'Apakah Anda yakin ingin melanjutkan?',
    'confirmLabel' => 'Ya, Lanjutkan',
    'danger' => true,
])
<div x-data="{ open: false }">
    <span @click="open = true">{{ $trigger }}</span>

    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="open = false" class="absolute inset-0 bg-slate-900/40"></div>
        <div @click.outside="open = false" @keydown.escape.window="open = false"
            class="relative bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-sm p-6">
            <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ $message }}</p>
            <div class="mt-5 flex items-center justify-end gap-2">
                <button type="button" @click="open = false" class="px-4 py-2 text-sm font-medium rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">Batal</button>
                <span @click="open = false">{{ $action }}</span>
            </div>
        </div>
    </div>
</div>

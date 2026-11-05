<div
    x-data="{
        open: false,
        q: '',
        results: [],
        loading: false,
        toggle() {
            this.open = ! this.open;
            if (this.open) { this.$nextTick(() => this.$refs.input.focus()); }
        },
        async run() {
            if (this.q.trim().length < 2) { this.results = []; return; }
            this.loading = true;
            try {
                const res = await fetch('{{ route('search.index') }}?q=' + encodeURIComponent(this.q), { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                this.results = json.data || [];
            } catch (e) {
                this.results = [];
            } finally {
                this.loading = false;
            }
        }
    }"
    @keydown.window.ctrl.k.prevent="toggle()"
    @keydown.window.meta.k.prevent="toggle()"
    @keydown.escape.window="open = false"
>
    <button @click="toggle()" class="flex items-center gap-2 p-2 rounded-full hover:bg-slate-100" aria-label="Pencarian global">
        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
        <span class="hidden lg:inline text-xs text-slate-400 border border-slate-200 rounded px-1.5 py-0.5">Ctrl K</span>
    </button>

    <div x-show="open" x-cloak class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-slate-900/40" @click="open = false"></div>
        <div class="relative max-w-lg mx-auto mt-24 bg-white rounded-xl shadow-xl border border-slate-200 overflow-hidden">
            <input x-ref="input" x-model="q" @input.debounce.300ms="run()"
                type="text" placeholder="Cari karyawan, cuti, lowongan..."
                class="w-full border-0 border-b border-slate-100 focus:ring-0 px-4 py-3 text-sm">
            <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                <template x-for="(r, i) in results" :key="i">
                    <a :href="r.url" class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50">
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 shrink-0" x-text="r.type"></span>
                        <span class="min-w-0">
                            <span class="block text-sm text-slate-800 truncate" x-text="r.label"></span>
                            <span class="block text-xs text-slate-500" x-text="r.sublabel"></span>
                        </span>
                    </a>
                </template>
                <p x-show="loading" class="px-4 py-6 text-center text-sm text-slate-500">Mencari...</p>
                <p x-show="!loading && q.length >= 2 && results.length === 0" class="px-4 py-6 text-center text-sm text-slate-500">Tidak ada hasil.</p>
                <p x-show="q.length < 2" class="px-4 py-6 text-center text-xs text-slate-400">Ketik minimal 2 karakter.</p>
            </div>
        </div>
    </div>
</div>

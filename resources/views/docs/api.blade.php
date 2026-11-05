<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dokumentasi API — {{ config('app.name', 'HRIS') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 font-sans antialiased">
<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8" x-data="apiDocs({{ \Illuminate\Support\Js::from($endpoints) }})">
    <header class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Dokumentasi API HRIS</h1>
            <p class="text-sm text-slate-500">Base URL: <code class="font-mono text-xs bg-slate-100 px-1 rounded">{{ url('/api/v1') }}</code></p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ url('/docs/api/openapi.json') }}" target="_blank" class="text-sm px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">OpenAPI JSON</a>
            <a href="{{ route('dashboard') }}" class="text-sm px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">Ke Aplikasi</a>
        </div>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <aside class="lg:col-span-1">
            <div class="bg-white rounded-xl border border-slate-200 p-3">
                <p class="text-[11px] font-semibold tracking-widest text-slate-400 px-2 mb-2">ENDPOINT</p>
                <template x-for="(ep, i) in endpoints" :key="i">
                    <button @click="select(i)" class="w-full text-left px-2 py-2 rounded-lg text-sm flex items-center gap-2" :class="active === i ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-slate-600 hover:bg-slate-50'">
                        <span class="text-[10px] font-mono px-1.5 py-0.5 rounded" :class="ep.method === 'GET' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'" x-text="ep.method"></span>
                        <span class="truncate" x-text="ep.path.replace('/api/v1','')"></span>
                    </button>
                </template>
            </div>
        </aside>

        <main class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono px-2 py-0.5 rounded" :class="current.method === 'GET' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'" x-text="current.method"></span>
                    <code class="text-sm font-mono text-slate-800" x-text="current.path"></code>
                </div>
                <h2 class="mt-3 text-lg font-semibold text-slate-900" x-text="current.title"></h2>
                <p class="mt-1 text-sm text-slate-600" x-text="current.description"></p>
                <p class="mt-2 text-xs" :class="current.auth ? 'text-amber-600' : 'text-emerald-600'"
                   x-text="current.auth ? 'Membutuhkan Bearer token' : 'Publik (tanpa token)'"></p>

                <div x-show="current.body" class="mt-4">
                    <p class="text-xs font-semibold text-slate-500 mb-1">Contoh Body</p>
                    <pre class="p-3 bg-slate-900 text-emerald-300 rounded-lg text-xs overflow-x-auto" x-text="JSON.stringify(current.body, null, 2)"></pre>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h3 class="text-sm font-semibold text-slate-900">Coba Endpoint</h3>
                <div class="mt-3 space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Bearer Token</label>
                        <input x-model="token" type="text" placeholder="Tempel token (tanpa 'id|')"
                            class="w-full text-sm rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 font-mono">
                        <p class="mt-1 text-[11px] text-slate-400">Belum punya token? Jalankan endpoint <strong>POST /login</strong> dengan email & password.</p>
                    </div>
                    <div x-show="current.body">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Body (JSON)</label>
                        <textarea x-model="bodyText" rows="5" class="w-full text-xs font-mono rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                    </div>
                    <button @click="send()" :disabled="loading"
                        class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700 disabled:opacity-50">
                        <span x-text="loading ? 'Mengirim...' : 'Kirim Permintaan'"></span>
                    </button>
                    <p x-show="error" class="text-xs text-red-600" x-text="error"></p>
                </div>

                <div x-show="response" class="mt-4">
                    <p class="text-xs font-semibold text-slate-500 mb-1">Respons <span x-text="'(' + status + ')'"></span></p>
                    <pre class="p-3 bg-slate-950 text-slate-100 rounded-lg text-xs overflow-x-auto max-h-96" x-text="response"></pre>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function apiDocs(endpoints) {
    return {
        endpoints,
        active: 0,
        token: '',
        bodyText: '',
        response: '',
        status: '',
        error: '',
        loading: false,
        get current() { return this.endpoints[this.active]; },
        init() { this.bodyText = this.current.body ? JSON.stringify(this.current.body, null, 2) : ''; },
        select(i) {
            this.active = i;
            this.response = ''; this.status = ''; this.error = '';
            this.bodyText = this.current.body ? JSON.stringify(this.current.body, null, 2) : '';
        },
        async send() {
            this.loading = true; this.error = ''; this.response = ''; this.status = '';
            try {
                const opts = { method: this.current.method, headers: { 'Accept': 'application/json' } };
                if (this.token) opts.headers['Authorization'] = 'Bearer ' + this.token;
                if (this.current.body) {
                    opts.headers['Content-Type'] = 'application/json';
                    opts.body = this.bodyText;
                }
                const res = await fetch(this.current.path, opts);
                this.status = res.status;
                const text = await res.text();
                try { this.response = JSON.stringify(JSON.parse(text), null, 2); }
                catch { this.response = text; }
            } catch (e) {
                this.error = 'Gagal mengirim: ' + e.message;
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
</body>
</html>

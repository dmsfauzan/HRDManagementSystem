@props(['url', 'title' => ''])
@php($enc = urlencode($url))
@php($encTitle = urlencode($title))
<div class="flex items-center gap-2">
    <span class="text-xs text-slate-500">Bagikan:</span>
    <a href="https://wa.me/?text={{ $encTitle }}%20{{ $enc }}" target="_blank" rel="noopener" class="p-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50" title="WhatsApp">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38c1.45.79 3.08 1.21 4.79 1.21 5.46 0 9.91-4.45 9.91-9.91S17.5 2 12.04 2zm5.8 14.01c-.24.68-1.4 1.3-1.93 1.34-.53.05-1.02.24-3.45-.72-2.93-1.16-4.78-4.15-4.92-4.34-.14-.19-1.18-1.57-1.18-3 0-1.43.75-2.13 1.02-2.42.24-.27.53-.34.72-.34.19 0 .38 0 .55.01.18.01.42-.07.65.5.24.58.82 2.01.89 2.16.07.15.12.32.02.51-.1.19-.15.31-.29.48-.14.17-.3.38-.43.51-.14.14-.29.29-.12.57.17.29.75 1.24 1.61 2.01 1.11.99 2.04 1.3 2.33 1.44.29.14.46.12.63-.07.17-.19.72-.84.91-1.13.19-.29.38-.24.65-.14.27.1 1.7.8 1.99.95.29.14.48.22.55.34.07.13.07.75-.17 1.43z"/></svg>
    </a>
    <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $enc }}" target="_blank" rel="noopener" class="p-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50" title="LinkedIn">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M4.98 3.5C4.98 4.88 3.87 6 2.5 6S0 4.88 0 3.5 1.12 1 2.5 1s2.48 1.12 2.48 2.5zM.5 8h4V24h-4V8zm7.5 0h3.8v2.2h.05c.53-1 1.83-2.2 3.77-2.2 4.03 0 4.78 2.65 4.78 6.1V24h-4v-8.6c0-2.05-.04-4.68-2.85-4.68-2.85 0-3.29 2.23-3.29 4.53V24h-4V8z"/></svg>
    </a>
    <button type="button" onclick="navigator.clipboard.writeText('{{ $url }}'); this.innerText='Tersalin!';" class="text-xs px-2 py-1 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50">Salin tautan</button>
</div>

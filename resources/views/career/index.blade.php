<x-public-layout :title="'Lowongan'">
    <div class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 mb-6">
        <div class="flex items-center gap-4">
            @if($company->logo_path)
                <img src="{{ route('company.logo') }}" alt="Logo" class="w-14 h-14 rounded-lg object-contain border border-slate-200">
            @else
                <span class="w-14 h-14 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold">{{ strtoupper(substr($company->name, 0, 2)) }}</span>
            @endif
            <div>
                <h1 class="text-xl font-bold text-slate-900">{{ $company->name }}</h1>
                <p class="text-sm text-slate-500">{{ $company->tagline }}</p>
            </div>
        </div>
        @if($company->about)
            <p class="mt-4 text-sm text-slate-600">{{ $company->about }}</p>
        @endif
        <div class="mt-4 flex flex-wrap gap-3 text-xs text-slate-500">
            @if($company->city)<span>📍 {{ $company->city }}</span>@endif
            @if($company->website)<a href="{{ $company->website }}" class="hover:underline">🌐 {{ $company->website }}</a>@endif
            @if($company->linkedin)<a href="{{ $company->linkedin }}" class="hover:underline">in LinkedIn</a>@endif
        </div>
    </div>

    <form method="GET" action="{{ route('career.index') }}" class="flex flex-wrap gap-2 mb-6">
        <x-text-input name="search" :value="request('search')" placeholder="Cari posisi..." class="flex-1 min-w-[200px]" />
        <x-select-input name="department_id" onchange="this.form.submit()">
            <option value="">Semua departemen</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
            @endforeach
        </x-select-input>
        <x-select-input name="work_mode" onchange="this.form.submit()">
            <option value="">Semua mode</option>
            <option value="onsite" @selected(request('work_mode') === 'onsite')>Onsite</option>
            <option value="hybrid" @selected(request('work_mode') === 'hybrid')>Hybrid</option>
            <option value="remote" @selected(request('work_mode') === 'remote')>Remote</option>
        </x-select-input>
        <x-select-input name="employment_type" onchange="this.form.submit()">
            <option value="">Semua tipe</option>
            <option value="permanent" @selected(request('employment_type') === 'permanent')>Tetap</option>
            <option value="contract" @selected(request('employment_type') === 'contract')>Kontrak</option>
            <option value="intern" @selected(request('employment_type') === 'intern')>Magang</option>
        </x-select-input>
        <x-primary-button type="submit">Cari</x-primary-button>
    </form>

    @if($postings->isEmpty())
        <div class="bg-white rounded-xl border border-slate-200 p-10 text-center text-sm text-slate-500">
            Belum ada lowongan yang sesuai.
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($postings as $posting)
                <x-job-card :posting="$posting" />
            @endforeach
        </div>
        <div class="mt-6">{{ $postings->links() }}</div>
    @endif
</x-public-layout>

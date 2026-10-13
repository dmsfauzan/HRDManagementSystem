<x-public-layout :title="$job->title">
    <a href="{{ route('career.index') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Semua lowongan</a>

    <div class="mt-4 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h1 class="text-xl font-bold text-slate-900">{{ $job->title }}</h1>
                <p class="text-sm text-slate-500 mt-1">
                    {{ $job->department->name ?? '—' }}
                    @if($job->position) &middot; {{ $job->position->name }} @endif
                    @if($job->location) &middot; {{ $job->location }} @endif
                    &middot; {{ \Illuminate\Support\Str::headline($job->employment_type) }}
                </p>
                @if($job->show_salary && $job->salary_min)
                    <p class="mt-2 text-sm text-emerald-700">
                        Gaji: Rp {{ number_format((float) $job->salary_min, 0, ',', '.') }}@if($job->salary_max) – Rp {{ number_format((float) $job->salary_max, 0, ',', '.') }}@endif
                    </p>
                @endif

                <div class="mt-6 prose prose-sm max-w-none text-slate-700 whitespace-pre-line">{{ $job->description }}</div>

                @if($job->requirements)
                    <h2 class="mt-6 text-sm font-semibold text-slate-900">Kualifikasi</h2>
                    <div class="mt-2 text-sm text-slate-700 whitespace-pre-line">{{ $job->requirements }}</div>
                @endif
            </div>
        </div>

        <div>
            <div class="bg-white rounded-xl border border-slate-200 p-6" id="lamar">
                <h2 class="text-base font-semibold text-slate-900">Lamar Posisi Ini</h2>
                <form method="POST" action="{{ route('career.apply', $job) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                    @csrf
                    <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">
                    <div>
                        <x-input-label for="candidate_name" value="Nama Lengkap *" />
                        <x-text-input id="candidate_name" name="candidate_name" class="mt-1 block w-full" :value="old('candidate_name')" required />
                    </div>
                    <div>
                        <x-input-label for="candidate_email" value="Email *" />
                        <x-text-input id="candidate_email" name="candidate_email" type="email" class="mt-1 block w-full" :value="old('candidate_email')" required />
                    </div>
                    <div>
                        <x-input-label for="candidate_phone" value="Nomor Telepon" />
                        <x-text-input id="candidate_phone" name="candidate_phone" class="mt-1 block w-full" :value="old('candidate_phone')" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="education" value="Pendidikan" />
                            <x-text-input id="education" name="education" class="mt-1 block w-full" :value="old('education')" />
                        </div>
                        <div>
                            <x-input-label for="experience_years" value="Pengalaman (tahun)" />
                            <x-text-input id="experience_years" name="experience_years" type="number" min="0" max="60" class="mt-1 block w-full" :value="old('experience_years')" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="cover_letter" value="Surat Lamaran" />
                        <x-textarea-input id="cover_letter" name="cover_letter" rows="3" class="mt-1">{{ old('cover_letter') }}</x-textarea-input>
                    </div>
                    <div>
                        <x-input-label for="cv" value="CV (PDF/DOC, maks 5 MB) *" />
                        <input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx" class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:bg-slate-100 file:text-slate-700" required>
                    </div>
                    <div>
                        <x-input-label for="photo" value="Foto (opsional)" />
                        <input type="file" id="photo" name="photo" accept="image/*" class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:bg-slate-100 file:text-slate-700">
                    </div>
                    <x-input-error :messages="$errors->all()" class="mt-1" />
                    <x-primary-button class="w-full justify-center">Kirim Lamaran</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-public-layout>

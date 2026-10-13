<x-public-layout :title="$job->title">
    <x-slot:meta>
        @php
            $empType = ['permanent' => 'FULL_TIME', 'contract' => 'CONTRACTOR', 'intern' => 'INTERN', 'probation' => 'FULL_TIME'][$job->employment_type] ?? 'FULL_TIME';
            $schema = [
                '@context' => 'https://schema.org/',
                '@type' => 'JobPosting',
                'title' => $job->title,
                'description' => strip_tags($job->description),
                'identifier' => ['@type' => 'PropertyValue', 'name' => $company->name, 'value' => $job->code],
                'datePosted' => $job->published_at?->toIso8601String(),
                'validThrough' => $job->closes_at?->endOfDay()->toIso8601String(),
                'employmentType' => $empType,
                'hiringOrganization' => array_filter([
                    '@type' => 'Organization',
                    'name' => $company->name,
                    'sameAs' => $company->website,
                ]),
                'jobLocation' => [
                    '@type' => 'Place',
                    'address' => array_filter([
                        '@type' => 'PostalAddress',
                        'streetAddress' => $company->address,
                        'addressLocality' => $company->city,
                    ]),
                ],
            ];
            if ($job->work_mode === 'remote') {
                $schema['jobLocationType'] = 'TELECOMMUTE';
            }
            if ($job->show_salary && $job->salary_min) {
                $schema['baseSalary'] = [
                    '@type' => 'MonetaryAmount',
                    'currency' => 'IDR',
                    'value' => array_filter([
                        '@type' => 'QuantitativeValue',
                        'minValue' => (float) $job->salary_min,
                        'maxValue' => $job->salary_max ? (float) $job->salary_max : null,
                        'unitText' => 'MONTH',
                    ]),
                ];
            }
        @endphp
        <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($job->description), 155) }}">
        <meta property="og:title" content="{{ $job->title }} — {{ $company->name }}">
        <meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($job->description), 155) }}">
        <meta property="og:type" content="website">
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    </x-slot:meta>

    <a href="{{ route('career.index') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Semua lowongan</a>

    @php
        $modeLabels = ['onsite' => 'Onsite', 'hybrid' => 'Hybrid', 'remote' => 'Remote'];
        $levelLabels = ['intern' => 'Intern', 'junior' => 'Junior', 'mid' => 'Mid', 'senior' => 'Senior', 'lead' => 'Lead'];
        $typeLabels = ['permanent' => 'Tetap', 'contract' => 'Kontrak', 'intern' => 'Magang', 'probation' => 'Percobaan'];
    @endphp

    <div class="mt-4 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <div class="flex items-start gap-4">
                    @if($company->logo_path)
                        <img src="{{ route('company.logo') }}" alt="Logo" class="w-12 h-12 rounded-lg object-contain border border-slate-200 shrink-0">
                    @endif
                    <div>
                        <h1 class="text-xl font-bold text-slate-900">{{ $job->title }}</h1>
                        <p class="text-sm text-slate-500 mt-0.5">{{ $company->name }} @if($job->location) &middot; {{ $job->location }} @endif</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-1.5">
                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ $modeLabels[$job->work_mode] ?? $job->work_mode }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ $levelLabels[$job->experience_level] ?? $job->experience_level }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ $typeLabels[$job->employment_type] ?? $job->employment_type }}</span>
                    @if($job->show_salary && $job->salary_min)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">Rp {{ number_format((float) $job->salary_min, 0, ',', '.') }}@if($job->salary_max)–{{ number_format((float) $job->salary_max, 0, ',', '.') }}@endif</span>
                    @endif
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-4 text-xs text-slate-500">
                    <span>{{ $applicantCount }} pelamar</span>
                    @if($job->closes_at)<span>Batas lamaran: {{ $job->closes_at->translatedFormat('d M Y') }}</span>@endif
                    <x-share-buttons :url="url()->current()" :title="$job->title" />
                </div>

                @if($job->responsibilities)
                    <h2 class="mt-6 text-sm font-semibold text-slate-900">Tanggung Jawab</h2>
                    <div class="mt-2 text-sm text-slate-700 whitespace-pre-line">{{ $job->responsibilities }}</div>
                @endif

                <h2 class="mt-6 text-sm font-semibold text-slate-900">Deskripsi Pekerjaan</h2>
                <div class="mt-2 text-sm text-slate-700 whitespace-pre-line">{{ $job->description }}</div>

                @if($job->requirements)
                    <h2 class="mt-6 text-sm font-semibold text-slate-900">Kualifikasi</h2>
                    <div class="mt-2 text-sm text-slate-700 whitespace-pre-line">{{ $job->requirements }}</div>
                @endif

                @if($job->benefits)
                    <h2 class="mt-6 text-sm font-semibold text-slate-900">Benefit</h2>
                    <div class="mt-2 text-sm text-slate-700 whitespace-pre-line">{{ $job->benefits }}</div>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="lg:sticky lg:top-20 space-y-6">
                <div class="bg-white rounded-xl border border-slate-200 p-6" id="lamar">
                    <h2 class="text-base font-semibold text-slate-900">Lamar Posisi Ini</h2>
                    @if($job->isClosed())
                        <div class="mt-3 p-3 rounded-lg bg-slate-100 text-sm text-slate-600">Lowongan ini sudah ditutup.</div>
                    @else
                        <form method="POST" action="{{ route('career.apply', $job) }}" enctype="multipart/form-data" class="mt-4" x-data="{ step: 1, steps: 3 }">
                            @csrf
                            <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">

                            <div class="flex items-center gap-2 mb-4">
                                @foreach([1, 2, 3] as $s)
                                    <div class="flex-1 h-1.5 rounded-full" :class="step >= {{ $s }} ? 'bg-emerald-500' : 'bg-slate-200'"></div>
                                @endforeach
                            </div>
                            <p class="text-xs text-slate-500 mb-2">Langkah <span x-text="step"></span> dari 3</p>

                            <div x-show="step === 1" class="space-y-3">
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
                                        <x-input-label for="domicile" value="Domisili" />
                                        <x-text-input id="domicile" name="domicile" class="mt-1 block w-full" :value="old('domicile')" />
                                    </div>
                                    <div>
                                        <x-input-label for="birth_date" value="Tgl Lahir" />
                                        <x-text-input id="birth_date" name="birth_date" type="date" class="mt-1 block w-full" :value="old('birth_date')" />
                                    </div>
                                </div>
                            </div>

                            <div x-show="step === 2" class="space-y-3">
                                <div>
                                    <x-input-label for="education" value="Pendidikan" />
                                    <x-text-input id="education" name="education" class="mt-1 block w-full" :value="old('education')" />
                                </div>
                                <div>
                                    <x-input-label for="experience_years" value="Pengalaman (tahun)" />
                                    <x-text-input id="experience_years" name="experience_years" type="number" min="0" max="60" class="mt-1 block w-full" :value="old('experience_years')" />
                                </div>
                                <div>
                                    <x-input-label for="linkedin_url" value="LinkedIn" />
                                    <x-text-input id="linkedin_url" name="linkedin_url" class="mt-1 block w-full" :value="old('linkedin_url')" placeholder="https://linkedin.com/in/..." />
                                </div>
                                <div>
                                    <x-input-label for="portfolio_url" value="Portofolio" />
                                    <x-text-input id="portfolio_url" name="portfolio_url" class="mt-1 block w-full" :value="old('portfolio_url')" placeholder="https://..." />
                                </div>
                                <div>
                                    <x-input-label for="expected_salary" value="Ekspektasi Gaji (Rp)" />
                                    <x-text-input id="expected_salary" name="expected_salary" type="number" min="0" class="mt-1 block w-full" :value="old('expected_salary')" />
                                </div>
                            </div>

                            <div x-show="step === 3" class="space-y-3">
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
                            </div>

                            <x-input-error :messages="$errors->all()" class="mt-2" />

                            <div class="mt-4 flex items-center justify-between gap-2">
                                <button type="button" x-show="step > 1" @click="step--" class="px-4 py-2 text-sm rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">Sebelumnya</button>
                                <button type="button" x-show="step < 3" @click="step++" class="ml-auto px-4 py-2 text-sm rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">Selanjutnya</button>
                                <x-primary-button x-show="step === 3" class="ml-auto">Kirim Lamaran</x-primary-button>
                            </div>
                        </form>
                    @endif
                </div>

                @if($similar->isNotEmpty())
                    <div class="bg-white rounded-xl border border-slate-200 p-6">
                        <h2 class="text-sm font-semibold text-slate-900 mb-3">Lowongan Lain</h2>
                        <div class="space-y-3">
                            @foreach($similar as $other)
                                <a href="{{ route('career.show', $other) }}" class="block">
                                    <span class="block text-sm font-medium text-slate-800 hover:text-emerald-700">{{ $other->title }}</span>
                                    <span class="block text-xs text-slate-500">{{ $other->department->name ?? '—' }} @if($other->location) &middot; {{ $other->location }} @endif</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-public-layout>

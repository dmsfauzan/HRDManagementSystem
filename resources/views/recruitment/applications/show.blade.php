<x-app-layout>
    <x-slot name="header">Detail Pelamar</x-slot>

    <x-page-header :title="$application->candidate_name" :subtitle="($application->jobPosting->title ?? '—') . ' — ' . $application->candidate_email" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <x-card title="Profil Kandidat" class="lg:col-span-2">
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-slate-500">Telepon</dt><dd class="text-slate-900">{{ $application->candidate_phone ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Domisili</dt><dd class="text-slate-900">{{ $application->domicile ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Pendidikan</dt><dd class="text-slate-900">{{ $application->education ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Pengalaman</dt><dd class="text-slate-900">{{ $application->experience_years !== null ? $application->experience_years.' tahun' : '—' }}</dd></div>
                <div><dt class="text-slate-500">Status</dt><dd><x-status-badge :status="$application->status" /></dd></div>
                <div><dt class="text-slate-500">Sumber</dt><dd class="text-slate-900">{{ $application->source ?? '—' }}</dd></div>
            </dl>

            <div class="mt-4 flex flex-wrap gap-3 text-sm">
                <a href="{{ route('recruitment.applications.cv', $application) }}" class="text-emerald-700 hover:underline">Unduh CV</a>
                @if($application->photo_path)
                    <a href="{{ route('recruitment.applications.photo', $application) }}" target="_blank" class="text-emerald-700 hover:underline">Lihat Foto</a>
                @endif
                @if($application->employee)
                    <a href="{{ route('employees.show', $application->employee) }}" class="text-emerald-700 hover:underline">Karyawan: {{ $application->employee->full_name }}</a>
                @endif
            </div>

            @if($application->cover_letter)
                <div class="mt-4">
                    <p class="text-slate-500 text-sm">Surat Lamaran</p>
                    <p class="text-sm text-slate-700 mt-1 whitespace-pre-line">{{ $application->cover_letter }}</p>
                </div>
            @endif

            @if($application->rejection_reason)
                <div class="mt-4 p-3 rounded-lg bg-red-50 text-sm text-red-700">Alasan ditolak: {{ $application->rejection_reason }}</div>
            @endif

            @if($canManage)
                <div class="mt-6 border-t border-slate-100 pt-4" x-data="{ to: '' }">
                    <p class="text-sm font-semibold text-slate-900">Ubah Status</p>
                    <form method="POST" action="{{ route('recruitment.applications.stage', $application) }}" class="mt-2 flex flex-wrap items-end gap-2">
                        @csrf @method('PATCH')
                        <x-select-input name="status" class="mt-1">
                            <option value="">— Pilih status —</option>
                            @foreach(['screening' => 'Screening', 'interview' => 'Interview', 'offer' => 'Offer', 'hired' => 'Hired', 'rejected' => 'Rejected', 'withdrawn' => 'Withdrawn'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-select-input>
                        <x-text-input name="note" placeholder="Catatan (wajib bila ditolak)" class="flex-1 min-w-[200px]" />
                        <x-primary-button>Simpan</x-primary-button>
                    </form>
                </div>

                @if($application->status === 'hired' && ! $application->employee)
                    <div class="mt-6 border-t border-slate-100 pt-4">
                        <p class="text-sm font-semibold text-slate-900">Konversi ke Karyawan</p>
                        <form method="POST" action="{{ route('recruitment.applications.convert', $application) }}" class="mt-2 flex flex-wrap items-end gap-2">
                            @csrf
                            <div>
                                <x-input-label for="join_date" value="Tanggal Mulai" />
                                <x-text-input id="join_date" name="join_date" type="date" class="mt-1" :value="tz_today()->toDateString()" />
                            </div>
                            <div>
                                <x-input-label for="employee_id" value="Employee ID (opsional)" />
                                <x-text-input id="employee_id" name="employee_id" class="mt-1" placeholder="otomatis bila kosong" />
                            </div>
                            <x-primary-button>Konversi</x-primary-button>
                        </form>
                    </div>
                @endif
            @endif
        </x-card>

        <div class="space-y-6">
            <x-card title="Wawancara">
                @if($canManage)
                    <form method="POST" action="{{ route('recruitment.applications.interviews.store', $application) }}" class="space-y-2 mb-4">
                        @csrf
                        <x-text-input name="scheduled_at" type="datetime-local" class="block w-full" required />
                        <x-select-input name="mode" class="block w-full">
                            <option value="online">Online</option>
                            <option value="onsite">Onsite</option>
                            <option value="phone">Telepon</option>
                        </x-select-input>
                        <x-text-input name="location" placeholder="Lokasi / tautan" class="block w-full" />
                        <x-secondary-button type="submit">Jadwalkan</x-secondary-button>
                    </form>
                @endif

                <div class="space-y-3">
                    @forelse($application->interviews as $interview)
                        <div class="border border-slate-100 rounded-lg p-3">
                            <p class="text-sm font-medium text-slate-800">{{ $interview->scheduled_at?->translatedFormat('d M Y H:i') ?? '—' }}</p>
                            <p class="text-xs text-slate-500">{{ \Illuminate\Support\Str::headline($interview->mode) }} @if($interview->location) &middot; {{ $interview->location }} @endif</p>
                            <div class="mt-1 flex items-center gap-2">
                                <x-status-badge :status="$interview->result === 'pass' ? 'approved' : ($interview->result === 'fail' ? 'rejected' : 'pending')" />
                                @if($interview->score !== null)<span class="text-xs text-slate-500">Skor {{ $interview->score }}</span>@endif
                            </div>
                            @if($canManage)
                                <form method="POST" action="{{ route('recruitment.interviews.result', $interview) }}" class="mt-2 flex flex-wrap items-center gap-2">
                                    @csrf @method('PATCH')
                                    <x-select-input name="result" class="text-xs">
                                        <option value="pass">Lulus</option>
                                        <option value="fail">Tidak lulus</option>
                                        <option value="pending">Pending</option>
                                    </x-select-input>
                                    <x-text-input name="score" type="number" min="0" max="100" placeholder="Skor" class="w-20" />
                                    <button type="submit" class="text-xs px-2 py-1 rounded bg-slate-200 text-slate-700 hover:bg-slate-300">Simpan</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <x-empty-state title="Belum ada wawancara" />
                    @endforelse
                </div>
            </x-card>

            <x-card title="Riwayat Tahapan">
                <div class="space-y-3">
                    @forelse($application->stageLogs as $log)
                        <div class="flex items-start gap-2">
                            <span class="mt-1.5 w-2 h-2 rounded-full bg-emerald-500"></span>
                            <div class="min-w-0">
                                <p class="text-sm text-slate-800">{{ \Illuminate\Support\Str::headline($log->to_status) }}</p>
                                <p class="text-xs text-slate-500">{{ $log->actor->name ?? 'Sistem' }} &middot; {{ $log->acted_at?->translatedFormat('d M Y H:i') }}</p>
                                @if($log->notes)<p class="text-xs text-slate-600 mt-0.5">{{ $log->notes }}</p>@endif
                            </div>
                        </div>
                    @empty
                        <x-empty-state title="Belum ada riwayat" />
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">Detail Pengajuan Cuti</x-slot>

    <x-page-header :title="$leave->leaveType->name ?? 'Pengajuan Cuti'" :subtitle="$leave->employee->full_name . ' — ' . $leave->employee->employee_id">
        <x-slot:actions>
            @if($leave->status === 'pending' && auth()->user()->can('decide', $leave))
                <form method="POST" action="{{ route('leave.decide', $leave) }}" class="flex items-center gap-2">
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="approved">
                    <x-primary-button type="submit">Setujui</x-primary-button>
                </form>
            @endif
            @can('cancel', $leave)
                <form method="POST" action="{{ route('leave.cancel', $leave) }}" onsubmit="return confirm('Batalkan pengajuan cuti ini?')">
                    @csrf @method('PATCH')
                    <x-secondary-button type="submit">Batalkan</x-secondary-button>
                </form>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <x-card title="Rincian" class="lg:col-span-2">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div><dt class="text-slate-500">Jenis Cuti</dt><dd class="font-medium text-slate-900">{{ $leave->leaveType->name ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Status</dt><dd><x-status-badge :status="$leave->status" /></dd></div>
                <div><dt class="text-slate-500">Mulai</dt><dd class="font-medium text-slate-900">{{ $leave->start_date->translatedFormat('d F Y') }}</dd></div>
                <div><dt class="text-slate-500">Selesai</dt><dd class="font-medium text-slate-900">{{ $leave->end_date->translatedFormat('d F Y') }}</dd></div>
                <div><dt class="text-slate-500">Total Hari Kerja</dt><dd class="font-medium text-slate-900">{{ rtrim(rtrim(number_format($leave->total_days, 2, ',', '.'), '0'), ',') }} hari</dd></div>
                @php($bal = $leave->employee->leaveBalances->firstWhere('leave_type_id', $leave->leave_type_id))
                @if($bal !== null)
                    <div><dt class="text-slate-500">Sisa Saldo {{ $leave->leaveType->name ?? '' }}</dt><dd class="font-medium text-slate-900">{{ rtrim(rtrim(number_format($bal->balance, 2, ',', '.'), '0'), ',') }} hari</dd></div>
                @endif
                <div><dt class="text-slate-500">Diajukan</dt><dd class="font-medium text-slate-900">{{ $leave->requested_at?->translatedFormat('d F Y H:i') ?? '—' }}</dd></div>
                @if($leave->approver)
                    <div><dt class="text-slate-500">Diputuskan Oleh</dt><dd class="font-medium text-slate-900">{{ $leave->approver->name }}</dd></div>
                    <div><dt class="text-slate-500">Waktu Keputusan</dt><dd class="font-medium text-slate-900">{{ $leave->decided_at?->translatedFormat('d F Y H:i') }}</dd></div>
                @endif
            </dl>

            <div class="mt-4">
                <p class="text-slate-500 text-sm">Alasan</p>
                <p class="text-sm text-slate-800 mt-1">{{ $leave->reason }}</p>
            </div>

            @if($leave->decision_note)
                <div class="mt-4">
                    <p class="text-slate-500 text-sm">Catatan Keputusan</p>
                    <p class="text-sm text-slate-800 mt-1">{{ $leave->decision_note }}</p>
                </div>
            @endif

            @if($leave->attachment_path)
                <div class="mt-4">
                    <a href="{{ route('leave.attachment', $leave) }}" class="text-sm text-emerald-700 hover:underline">Lihat lampiran</a>
                </div>
            @endif

            @if($leave->status === 'pending' && auth()->user()->can('decide', $leave))
                <div class="mt-6 border-t border-slate-100 pt-4" x-data="{ open: false }">
                    <button @click="open=!open" type="button" class="text-sm text-red-600 hover:underline">Tolak pengajuan ini</button>
                    <form x-show="open" x-cloak method="POST" action="{{ route('leave.decide', $leave) }}" class="mt-3 space-y-3">
                        @csrf @method('PATCH')
                        <input type="hidden" name="action" value="rejected">
                        <x-textarea-input name="decision_note" rows="3" placeholder="Alasan penolakan (wajib)" required></x-textarea-input>
                        <x-danger-button type="submit">Tolak Pengajuan</x-danger-button>
                    </form>
                </div>
            @endif
        </x-card>

        <x-card title="Riwayat Keputusan">
            <div class="space-y-4">
                @forelse($leave->approvalActions as $action)
                    <div class="flex gap-3">
                        <span class="mt-1 w-2 h-2 rounded-full {{ $action->action === 'approved' ? 'bg-emerald-500' : ($action->action === 'rejected' ? 'bg-red-500' : 'bg-slate-400') }}"></span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-800">{{ \Illuminate\Support\Str::headline($action->action) }}</p>
                            <p class="text-xs text-slate-500">{{ $action->actor?->name ?? 'Sistem' }} &middot; {{ $action->acted_at?->translatedFormat('d M Y H:i') }}</p>
                            @if($action->notes)<p class="text-xs text-slate-600 mt-1">{{ $action->notes }}</p>@endif
                        </div>
                    </div>
                @empty
                    <x-empty-state title="Belum ada riwayat" />
                @endforelse
            </div>
        </x-card>
    </div>
</x-app-layout>

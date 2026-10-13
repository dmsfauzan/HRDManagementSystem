<?php

namespace App\Services;

use App\Models\ApplicationStageLog;
use App\Models\Employee;
use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\User;
use App\Notifications\ApplicationReceived;
use App\Notifications\CandidateStageChanged;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RecruitmentService
{
    /** Transisi status yang diizinkan (state machine). */
    private const TRANSITIONS = [
        'applied' => ['screening', 'rejected', 'withdrawn'],
        'screening' => ['interview', 'rejected', 'withdrawn'],
        'interview' => ['offer', 'rejected', 'withdrawn'],
        'offer' => ['hired', 'rejected', 'withdrawn'],
        'hired' => [],
        'rejected' => [],
        'withdrawn' => [],
    ];

    public function submitApplication(JobPosting $posting, array $data, UploadedFile $cv, ?UploadedFile $photo = null): JobApplication
    {
        if (! $posting->isPublished()) {
            throw ValidationException::withMessages(['job' => 'Lowongan ini tidak tersedia.']);
        }

        return DB::transaction(function () use ($posting, $data, $cv, $photo) {
            $application = JobApplication::create([
                'job_posting_id' => $posting->id,
                'candidate_name' => $data['candidate_name'],
                'candidate_email' => $data['candidate_email'],
                'candidate_phone' => $data['candidate_phone'] ?? null,
                'domicile' => $data['domicile'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'education' => $data['education'] ?? null,
                'experience_years' => $data['experience_years'] ?? null,
                'source' => $data['source'] ?? 'career_page',
                'cover_letter' => $data['cover_letter'] ?? null,
                'cv_path' => 'pending',
                'status' => 'applied',
            ]);

            $application->cv_path = $this->store($application, $cv, 'cv');
            if ($photo) {
                $application->photo_path = $this->store($application, $photo, 'photo');
            }
            $application->save();

            $this->log($application, null, 'applied', null, 'Lamaran diterima.');

            $this->notifyHrd($application);
            $this->notifyCandidate($application, 'applied');

            return $application;
        });
    }

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public function moveStage(JobApplication $application, string $to, ?User $actor = null, ?string $note = null): JobApplication
    {
        $from = $application->status;

        if (! $this->canTransition($from, $to)) {
            throw ValidationException::withMessages(['status' => "Transisi dari '{$from}' ke '{$to}' tidak diizinkan."]);
        }

        if ($to === 'rejected' && ! $note) {
            throw ValidationException::withMessages(['note' => 'Alasan penolakan wajib diisi.']);
        }

        return DB::transaction(function () use ($application, $from, $to, $actor, $note) {
            $application->status = $to;

            if ($to === 'rejected') {
                $application->rejection_reason = $note;
            }

            if ($to === 'hired') {
                $application->hired_at = now();
            }

            $application->save();

            $this->log($application, $from, $to, $actor, $note);
            $this->notifyCandidate($application, $to);

            AuditLogger::log('recruitment.stage_changed', $application, ['status' => $from], ['status' => $to]);

            return $application;
        });
    }

    public function scheduleInterview(JobApplication $application, array $data, User $actor): Interview
    {
        $interview = $application->interviews()->create([
            'scheduled_at' => $data['scheduled_at'],
            'mode' => $data['mode'] ?? 'online',
            'location' => $data['location'] ?? null,
            'interviewers' => $data['interviewers'] ?? null,
            'result' => 'pending',
        ]);

        if ($application->status === 'screening') {
            $this->moveStage($application, 'interview', $actor, 'Dijadwalkan wawancara.');
        }

        AuditLogger::log('recruitment.interview_scheduled', $interview, [], ['application_id' => $application->id]);

        return $interview;
    }

    public function recordInterviewResult(Interview $interview, string $result, ?int $score, ?string $notes, User $actor): Interview
    {
        $interview->update([
            'result' => $result,
            'score' => $score,
            'notes' => $notes,
            'decided_by' => $actor->id,
        ]);

        AuditLogger::log('recruitment.interview_result', $interview, [], ['result' => $result]);

        return $interview;
    }

    public function convertToEmployee(JobApplication $application, User $actor, array $overrides = []): Employee
    {
        if ($application->employee_id) {
            throw ValidationException::withMessages(['employee' => 'Kandidat ini sudah dikonversi menjadi karyawan.']);
        }

        if ($application->status !== 'hired') {
            throw ValidationException::withMessages(['status' => 'Hanya kandidat berstatus "hired" yang dapat dikonversi.']);
        }

        $posting = $application->jobPosting;

        return DB::transaction(function () use ($application, $posting, $overrides) {
            $employee = app(EmployeeService::class)->create([
                'employee_id' => $overrides['employee_id'] ?? $this->generateEmployeeId($posting),
                'full_name' => $application->candidate_name,
                'email' => $application->candidate_email,
                'phone' => $application->candidate_phone,
                'department_id' => $overrides['department_id'] ?? $posting?->department_id,
                'position_id' => $overrides['position_id'] ?? $posting?->position_id,
                'join_date' => $overrides['join_date'] ?? tz_today()->toDateString(),
                'employment_type' => $overrides['employment_type'] ?? $posting?->employment_type ?? 'permanent',
                'employment_status' => 'active',
                'create_account' => $overrides['create_account'] ?? false,
                'account_email' => $application->candidate_email,
                'account_password' => $overrides['account_password'] ?? null,
                'account_role' => 'karyawan',
            ]);

            $application->update(['employee_id' => $employee->id]);

            AuditLogger::log('recruitment.converted', $application, [], ['employee_id' => $employee->id]);

            return $employee;
        });
    }

    private function generateEmployeeId(?JobPosting $posting): string
    {
        $prefix = $posting?->department?->code ?? 'EMP';
        $seq = Employee::withTrashed()->where('employee_id', 'like', $prefix.'-%')->count() + 1;

        return sprintf('%s-%03d', $prefix, $seq);
    }

    private function store(JobApplication $application, UploadedFile $file, string $kind): string
    {
        $ext = $file->getClientOriginalExtension() ?: 'bin';
        $path = sprintf('recruitment/%d/%s.%s', $application->id, $kind, $ext);
        Storage::disk('private')->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }

    private function log(JobApplication $application, ?string $from, string $to, ?User $actor, ?string $notes): void
    {
        ApplicationStageLog::create([
            'job_application_id' => $application->id,
            'from_status' => $from,
            'to_status' => $to,
            'actor_id' => $actor?->id,
            'notes' => $notes,
            'acted_at' => now(),
        ]);
    }

    private function notifyHrd(JobApplication $application): void
    {
        $recipients = User::permission('recruitment.manage')->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new ApplicationReceived($application));
        }
    }

    private function notifyCandidate(JobApplication $application, string $status): void
    {
        Notification::route('mail', $application->candidate_email)
            ->notify(new CandidateStageChanged($application, $status));
    }
}

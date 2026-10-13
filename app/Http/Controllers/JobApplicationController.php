<?php

namespace App\Http\Controllers;

use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Services\RecruitmentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JobApplicationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly RecruitmentService $service) {}

    public function index(Request $request): View
    {
        $this->guardView();

        $applications = $this->baseQuery($request)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('recruitment.applications.index', [
            'applications' => $applications,
            'postings' => JobPosting::orderBy('title')->get(),
            'canManage' => auth()->user()->can('recruitment.manage'),
        ]);
    }

    public function kanban(Request $request): View
    {
        $this->guardView();

        $columns = ['applied', 'screening', 'interview', 'offer', 'hired', 'rejected'];
        $byStatus = [];

        $applications = $this->baseQuery($request)->get();
        foreach ($columns as $status) {
            $byStatus[$status] = $applications->where('status', $status)->values();
        }

        return view('recruitment.applications.kanban', [
            'byStatus' => $byStatus,
            'postings' => JobPosting::orderBy('title')->get(),
            'canManage' => auth()->user()->can('recruitment.manage'),
        ]);
    }

    public function show(JobApplication $jobApplication): View
    {
        $this->guardView();

        $jobApplication->load(['jobPosting.department', 'stageLogs.actor', 'interviews.decider', 'employee']);

        return view('recruitment.applications.show', [
            'application' => $jobApplication,
            'canManage' => auth()->user()->can('recruitment.manage'),
        ]);
    }

    public function moveStage(Request $request, JobApplication $jobApplication): RedirectResponse
    {
        abort_unless($request->user()->can('recruitment.manage'), 403);

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', JobApplication::STATUSES)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->service->moveStage($jobApplication, $data['status'], $request->user(), $data['note'] ?? null);
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Status pelamar diperbarui.');
    }

    public function storeInterview(Request $request, JobApplication $jobApplication): RedirectResponse
    {
        abort_unless($request->user()->can('recruitment.manage'), 403);

        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'mode' => ['required', 'in:online,onsite,phone'],
            'location' => ['nullable', 'string', 'max:150'],
            'interviewers' => ['nullable', 'array'],
            'interviewers.*' => ['exists:users,id'],
        ], [
            'scheduled_at.required' => 'Waktu wawancara wajib diisi.',
        ]);

        $this->service->scheduleInterview($jobApplication, $data, $request->user());

        return back()->with('success', 'Jadwal wawancara disimpan.');
    }

    public function recordResult(Request $request, Interview $interview): RedirectResponse
    {
        abort_unless($request->user()->can('recruitment.manage'), 403);

        $data = $request->validate([
            'result' => ['required', 'in:pending,pass,fail'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->service->recordInterviewResult($interview, $data['result'], $data['score'] ?? null, $data['notes'] ?? null, $request->user());

        return back()->with('success', 'Hasil wawancara disimpan.');
    }

    public function convert(Request $request, JobApplication $jobApplication): RedirectResponse
    {
        abort_unless($request->user()->can('recruitment.manage'), 403);

        $data = $request->validate([
            'join_date' => ['nullable', 'date'],
            'employee_id' => ['nullable', 'string', 'max:30', 'unique:employees,employee_id'],
        ]);

        try {
            $employee = $this->service->convertToEmployee($jobApplication, $request->user(), array_filter($data));
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('employees.show', $employee)->with('success', 'Kandidat berhasil dikonversi menjadi karyawan.');
    }

    public function cv(Request $request, JobApplication $jobApplication): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user->can('recruitment.view'), 403);

        abort_unless($jobApplication->cv_path && Storage::disk('private')->exists($jobApplication->cv_path), 404);

        return Storage::disk('private')->download($jobApplication->cv_path, 'cv-'.$jobApplication->id.'.pdf');
    }

    public function photo(Request $request, JobApplication $jobApplication): StreamedResponse
    {
        abort_unless($request->user()->can('recruitment.view'), 403);

        abort_unless($jobApplication->photo_path && Storage::disk('private')->exists($jobApplication->photo_path), 404);

        return Storage::disk('private')->response($jobApplication->photo_path);
    }

    private function guardView(): void
    {
        abort_unless(auth()->user()->can('recruitment.view'), 403);
    }

    private function baseQuery(Request $request)
    {
        $query = JobApplication::with(['jobPosting.department']);

        if ($request->filled('posting_id')) {
            $query->where('job_posting_id', $request->integer('posting_id'));
        }

        return $query;
    }
}

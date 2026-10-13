<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfile;
use App\Models\Department;
use App\Models\JobPosting;
use App\Services\RecruitmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CareerController extends Controller
{
    public function __construct(private readonly RecruitmentService $service) {}

    public function index(Request $request): View
    {
        $postings = JobPosting::published()
            ->with(['department', 'position'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(fn ($sub) => $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%"));
            })
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($request->filled('work_mode'), fn ($q) => $q->where('work_mode', $request->string('work_mode')))
            ->when($request->filled('employment_type'), fn ($q) => $q->where('employment_type', $request->string('employment_type')))
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('career.index', [
            'postings' => $postings,
            'company' => CompanyProfile::current(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function show(JobPosting $job): View
    {
        abort_unless($job->isPublished(), 404);

        $job->load(['department', 'position']);

        $similar = JobPosting::published()
            ->whereKeyNot($job->id)
            ->when($job->department_id, fn ($q) => $q->where('department_id', $job->department_id))
            ->orderByDesc('published_at')
            ->take(4)
            ->get();

        return view('career.show', [
            'job' => $job,
            'company' => CompanyProfile::current(),
            'similar' => $similar,
            'applicantCount' => $job->applications()->count(),
        ]);
    }

    public function apply(Request $request, JobPosting $job): RedirectResponse
    {
        abort_unless($job->isPublished(), 404);

        if ($job->isClosed()) {
            return back()->with('error', 'Lowongan ini sudah ditutup.');
        }

        if ($request->filled('website')) {
            return redirect()->route('career.index');
        }

        $data = $request->validate([
            'candidate_name' => ['required', 'string', 'max:150'],
            'candidate_email' => ['required', 'email', 'max:150'],
            'candidate_phone' => ['nullable', 'string', 'max:30'],
            'domicile' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'education' => ['nullable', 'string', 'max:100'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
            'expected_salary' => ['nullable', 'numeric', 'min:0'],
            'cover_letter' => ['nullable', 'string', 'max:3000'],
            'cv' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [
            'candidate_name.required' => 'Nama wajib diisi.',
            'candidate_email.required' => 'Email wajib diisi.',
            'candidate_email.email' => 'Format email tidak valid.',
            'cv.required' => 'CV wajib diunggah.',
            'cv.mimes' => 'CV harus PDF/DOC/DOCX.',
            'cv.max' => 'Ukuran CV maksimal 5 MB.',
            'linkedin_url.url' => 'URL LinkedIn tidak valid.',
            'portfolio_url.url' => 'URL portofolio tidak valid.',
        ]);

        try {
            $application = $this->service->submitApplication($job, $data, $request->file('cv'), $request->file('photo'));
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('career.success', ['token' => $application->status_token]);
    }

    public function success(string $token): View
    {
        $application = $this->service->findByToken($token);

        abort_unless($application, 404);

        return view('career.success', [
            'company' => CompanyProfile::current(),
            'application' => $application,
        ]);
    }

    public function status(string $token): View
    {
        $application = $this->service->findByToken($token);

        abort_unless($application, 404);

        return view('career.status', [
            'company' => CompanyProfile::current(),
            'application' => $application,
        ]);
    }
}

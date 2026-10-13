<?php

namespace App\Http\Controllers;

use App\Models\JobPosting;
use App\Services\RecruitmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CareerController extends Controller
{
    public function __construct(private readonly RecruitmentService $service) {}

    public function index(): View
    {
        $postings = JobPosting::published()
            ->with(['department', 'position'])
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('career.index', compact('postings'));
    }

    public function show(JobPosting $job): View
    {
        abort_unless($job->isPublished(), 404);

        $job->load(['department', 'position']);

        return view('career.show', ['job' => $job]);
    }

    public function apply(Request $request, JobPosting $job): RedirectResponse
    {
        abort_unless($job->isPublished(), 404);

        // Honeypot anti-spam.
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
            'cover_letter' => ['nullable', 'string', 'max:3000'],
            'cv' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [
            'candidate_name.required' => 'Nama wajib diisi.',
            'candidate_email.required' => 'Email wajib diisi.',
            'cv.required' => 'CV wajib diunggah.',
            'cv.mimes' => 'CV harus PDF/DOC/DOCX.',
            'cv.max' => 'Ukuran CV maksimal 5 MB.',
        ]);

        try {
            $application = $this->service->submitApplication($job, $data, $request->file('cv'), $request->file('photo'));
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('career.success')->with('application_email', $application->candidate_email);
    }

    public function success(): View
    {
        return view('career.success');
    }
}

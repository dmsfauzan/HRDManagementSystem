<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\JobPosting;
use App\Models\Position;
use App\Services\AuditLogger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JobPostingController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        abort_unless(auth()->user()->can('recruitment.view'), 403);

        $postings = JobPosting::with(['department', 'position'])
            ->withCount('applications')
            ->latest()
            ->paginate(15);

        return view('recruitment.postings.index', [
            'postings' => $postings,
            'canManage' => auth()->user()->can('recruitment.manage'),
        ]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()->can('recruitment.manage'), 403);

        return view('recruitment.postings.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('recruitment.manage'), 403);

        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;

        $posting = JobPosting::create($data);

        AuditLogger::log('recruitment.posting_created', $posting, [], ['title' => $posting->title]);

        return redirect()->route('recruitment.postings.index')->with('success', 'Lowongan berhasil dibuat.');
    }

    public function edit(JobPosting $jobPosting): View
    {
        abort_unless(auth()->user()->can('recruitment.manage'), 403);

        return view('recruitment.postings.edit', array_merge($this->formData(), ['posting' => $jobPosting]));
    }

    public function update(Request $request, JobPosting $jobPosting): RedirectResponse
    {
        abort_unless($request->user()->can('recruitment.manage'), 403);

        $jobPosting->update($this->validateData($request, $jobPosting));

        AuditLogger::log('recruitment.posting_updated', $jobPosting, [], ['title' => $jobPosting->title]);

        return redirect()->route('recruitment.postings.index')->with('success', 'Lowongan berhasil diperbarui.');
    }

    public function publish(JobPosting $jobPosting): RedirectResponse
    {
        abort_unless(auth()->user()->can('recruitment.manage'), 403);

        $jobPosting->update(['status' => JobPosting::STATUS_PUBLISHED, 'published_at' => now(), 'closed_at' => null]);

        AuditLogger::log('recruitment.posting_published', $jobPosting, [], ['status' => 'published']);

        return back()->with('success', 'Lowongan diterbitkan.');
    }

    public function close(JobPosting $jobPosting): RedirectResponse
    {
        abort_unless(auth()->user()->can('recruitment.manage'), 403);

        $jobPosting->update(['status' => JobPosting::STATUS_CLOSED, 'closed_at' => now()]);

        AuditLogger::log('recruitment.posting_closed', $jobPosting, [], ['status' => 'closed']);

        return back()->with('success', 'Lowongan ditutup.');
    }

    public function destroy(JobPosting $jobPosting): RedirectResponse
    {
        abort_unless(auth()->user()->can('recruitment.manage'), 403);

        AuditLogger::log('recruitment.posting_deleted', $jobPosting, ['title' => $jobPosting->title], []);
        $jobPosting->delete();

        return redirect()->route('recruitment.postings.index')->with('success', 'Lowongan dihapus.');
    }

    private function validateData(Request $request, ?JobPosting $posting = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('job_postings', 'code')->ignore($posting)],
            'title' => ['required', 'string', 'max:150'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'description' => ['required', 'string', 'max:5000'],
            'responsibilities' => ['nullable', 'string', 'max:5000'],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'benefits' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:100'],
            'work_mode' => ['required', Rule::in(['onsite', 'hybrid', 'remote'])],
            'experience_level' => ['required', Rule::in(['intern', 'junior', 'mid', 'senior', 'lead'])],
            'employment_type' => ['required', Rule::in(['permanent', 'contract', 'intern', 'probation'])],
            'quota' => ['required', 'integer', 'min:1', 'max:100'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0', 'gte:salary_min'],
            'show_salary' => ['boolean'],
            'closes_at' => ['nullable', 'date'],
        ], [
            'code.required' => 'Kode lowongan wajib diisi.',
            'code.unique' => 'Kode lowongan sudah digunakan.',
            'title.required' => 'Judul lowongan wajib diisi.',
            'description.required' => 'Deskripsi wajib diisi.',
            'salary_max.gte' => 'Gaji maksimum tidak boleh kurang dari minimum.',
        ]);

        $data['show_salary'] = $request->boolean('show_salary', false);

        return $data;
    }

    private function formData(): array
    {
        return [
            'departments' => Department::orderBy('name')->get(),
            'positions' => Position::orderBy('name')->get(),
        ];
    }
}

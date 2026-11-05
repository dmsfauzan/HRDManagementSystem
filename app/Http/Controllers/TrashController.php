<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Department;
use App\Models\Document;
use App\Models\Employee;
use App\Models\JobPosting;
use App\Models\Position;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrashController extends Controller
{
    /** @var array<string, array{model: class-string<Model>, label: string, permission: string, title: string}> */
    private const TYPES = [
        'employees' => ['model' => Employee::class, 'label' => 'Karyawan', 'permission' => 'employees.manage', 'title' => 'full_name'],
        'departments' => ['model' => Department::class, 'label' => 'Departemen', 'permission' => 'departments.manage', 'title' => 'name'],
        'positions' => ['model' => Position::class, 'label' => 'Jabatan', 'permission' => 'positions.manage', 'title' => 'name'],
        'announcements' => ['model' => Announcement::class, 'label' => 'Pengumuman', 'permission' => 'leave.manage', 'title' => 'title'],
        'documents' => ['model' => Document::class, 'label' => 'Dokumen', 'permission' => 'documents.manage', 'title' => 'title'],
        'job-postings' => ['model' => JobPosting::class, 'label' => 'Lowongan', 'permission' => 'recruitment.manage', 'title' => 'title'],
        'users' => ['model' => User::class, 'label' => 'Pengguna', 'permission' => 'users.manage', 'title' => 'name'],
    ];

    public function index(Request $request): View
    {
        $allowed = $this->allowedTypes($request);

        abort_if($allowed === [], 403);

        $type = $request->string('type', array_key_first($allowed))->toString();
        abort_unless(isset($allowed[$type]), 403);

        $config = self::TYPES[$type];
        $records = $config['model']::onlyTrashed()
            ->latest('deleted_at')
            ->paginate(20)
            ->withQueryString();

        return view('trash.index', [
            'allowed' => $allowed,
            'type' => $type,
            'records' => $records,
            'titleColumn' => $config['title'],
        ]);
    }

    public function restore(Request $request, string $type, int $id): RedirectResponse
    {
        $allowed = $this->allowedTypes($request);
        abort_unless(isset($allowed[$type]), 403);

        $config = self::TYPES[$type];
        $record = $config['model']::onlyTrashed()->findOrFail($id);
        $record->restore();

        AuditLogger::log('trash.restored', $record, [], ['type' => $type]);

        return back()->with('success', "{$config['label']} berhasil dipulihkan.");
    }

    /**
     * @return array<string, array{model: class-string<Model>, label: string, permission: string, title: string}>
     */
    private function allowedTypes(Request $request): array
    {
        return array_filter(
            self::TYPES,
            fn ($config) => $request->user()->can($config['permission'])
        );
    }
}

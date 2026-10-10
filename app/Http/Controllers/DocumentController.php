<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use App\Models\Employee;
use App\Services\AuditLogger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless($user->can('documents.manage') || $user->employee, 403);

        $query = Document::with(['employee', 'uploader']);

        if ($user->can('documents.manage')) {
            $query->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')));
        } else {
            $query->where('employee_id', $user->employee->id);
        }

        $documents = $query->latest()->paginate(20)->withQueryString();

        $employees = $user->can('documents.manage')
            ? Employee::orderBy('full_name')->get()
            : collect();

        return view('documents.index', [
            'documents' => $documents,
            'employees' => $employees,
            'canManage' => $user->can('documents.manage'),
        ]);
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $employee = Employee::findOrFail($request->integer('employee_id'));
        $file = $request->file('file');

        $document = Document::create([
            'employee_id' => $employee->id,
            'title' => $request->string('title'),
            'category' => $request->input('category'),
            'file_path' => 'pending',
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        $path = sprintf('documents/%d/%d.%s', $employee->id, $document->id, $file->getClientOriginalExtension() ?: 'bin');
        Storage::disk('private')->put($path, file_get_contents($file->getRealPath()));
        $document->update(['file_path' => $path]);

        AuditLogger::log('document.uploaded', $document, [], ['employee_id' => $employee->id, 'title' => $document->title]);

        return back()->with('success', 'Dokumen berhasil diunggah.');
    }

    public function download(Request $request, Document $document): StreamedResponse
    {
        $user = $request->user();
        $allowed = $user->can('documents.manage') || ($user->employee && $user->employee->id === $document->employee_id);
        abort_unless($allowed, 403);

        abort_unless(Storage::disk('private')->exists($document->file_path), 404);

        return Storage::disk('private')->download($document->file_path, $document->title);
    }

    public function destroy(Document $document): RedirectResponse
    {
        abort_unless(auth()->user()->can('documents.manage'), 403);

        if (Storage::disk('private')->exists($document->file_path)) {
            Storage::disk('private')->delete($document->file_path);
        }

        AuditLogger::log('document.deleted', $document, ['title' => $document->title], []);
        $document->delete();

        return back()->with('success', 'Dokumen dihapus.');
    }
}

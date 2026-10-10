<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnnouncementRequest;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use App\Services\AuditLogger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    use AuthorizesRequests;

    private function guard(): void
    {
        abort_unless(auth()->user()->can('leave.manage'), 403);
    }

    public function index(Request $request): View
    {
        $canManage = $request->user()->can('leave.manage');

        $announcements = ($canManage ? Announcement::with('author') : Announcement::published()->with('author'))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(15);

        return view('announcements.index', compact('announcements', 'canManage'));
    }

    public function create(): View
    {
        $this->guard();

        return view('announcements.create');
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $isPublish = $request->string('action')->toString() === 'publish';

        $announcement = Announcement::create([
            'title' => $request->string('title'),
            'body' => $request->string('body'),
            'created_by' => $request->user()->id,
            'published_at' => $isPublish ? now() : null,
            'is_active' => $isPublish,
        ]);

        if ($isPublish) {
            $this->broadcast($announcement);
        }

        AuditLogger::log('announcement.'.($isPublish ? 'published' : 'drafted'), $announcement, [], ['title' => $announcement->title]);

        return redirect()->route('announcements.index')->with('success', $isPublish ? 'Pengumuman diterbitkan.' : 'Draf tersimpan.');
    }

    public function publish(Announcement $announcement): RedirectResponse
    {
        $this->guard();

        if (! $announcement->is_active) {
            $announcement->update(['is_active' => true, 'published_at' => now()]);
            $this->broadcast($announcement);
            AuditLogger::log('announcement.published', $announcement, [], ['title' => $announcement->title]);

            return back()->with('success', 'Pengumuman diterbitkan.');
        }

        return back();
    }

    public function unpublish(Announcement $announcement): RedirectResponse
    {
        $this->guard();

        $announcement->update(['is_active' => false]);
        AuditLogger::log('announcement.unpublished', $announcement, [], ['title' => $announcement->title]);

        return back()->with('success', 'Pengumuman ditarik.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $this->guard();

        AuditLogger::log('announcement.deleted', $announcement, ['title' => $announcement->title], []);
        $announcement->delete();

        return redirect()->route('announcements.index')->with('success', 'Pengumuman dihapus.');
    }

    private function broadcast(Announcement $announcement): void
    {
        $recipients = User::where('is_active', true)->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new AnnouncementPublished($announcement));
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function index(Request $request): View
    {
        $currentId = $request->session()->getId();

        $sessions = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($session) use ($currentId) {
                $session->is_current = $session->id === $currentId;
                $session->last_active_at = Carbon::createFromTimestamp($session->last_activity, 'Asia/Jakarta');
                $session->is_this_device = $session->ip_address === request()->ip();

                return $session;
            });

        return view('sessions.index', compact('sessions', 'currentId'));
    }

    public function destroy(Request $request, string $session): RedirectResponse
    {
        $record = DB::table('sessions')
            ->where('id', $session)
            ->where('user_id', $request->user()->id)
            ->first();

        abort_unless($record, 404);

        if ($session === $request->session()->getId()) {
            return back()->with('error', 'Anda tidak dapat mencabut sesi yang sedang aktif digunakan.');
        }

        DB::table('sessions')->where('id', $session)->delete();

        AuditLogger::log('auth.session_revoked', null, [], [
            'session_id' => $session,
            'ip_address' => $record->ip_address,
        ]);

        return back()->with('success', 'Sesi berhasil dicabut.');
    }
}

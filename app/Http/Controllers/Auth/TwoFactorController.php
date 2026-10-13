<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function __construct(private readonly TwoFactorService $service) {}

    public function challenge(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('two_factor_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']], [
            'code.required' => 'Kode verifikasi wajib diisi.',
            'code.digits' => 'Kode verifikasi harus 6 angka.',
        ]);

        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'Sesi verifikasi tidak ditemukan. Silakan login ulang.']);
        }

        if (! $this->service->verify($user, $request->string('code')->toString())) {
            return back()->withErrors(['code' => 'Kode verifikasi salah atau sudah kedaluwarsa.']);
        }

        Auth::login($user);
        $request->session()->forget('two_factor_user_id');
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        $this->service->send($user);

        return back()->with('status', 'Kode verifikasi baru telah dikirim ke email Anda.');
    }

    private function pendingUser(Request $request): ?User
    {
        $id = $request->session()->get('two_factor_user_id');

        return $id ? User::find($id) : null;
    }
}

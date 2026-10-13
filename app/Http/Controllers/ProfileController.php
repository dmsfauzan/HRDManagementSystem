<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'avatar.required' => 'Pilih berkas foto.',
            'avatar.image' => 'Berkas harus berupa gambar.',
            'avatar.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'avatar.max' => 'Ukuran foto maksimal 2 MB.',
        ]);

        $user = $request->user();

        if ($user->avatar_path && Storage::disk('private')->exists($user->avatar_path)) {
            Storage::disk('private')->delete($user->avatar_path);
        }

        $ext = $request->file('avatar')->getClientOriginalExtension() ?: 'jpg';
        $path = sprintf('avatars/%d.%s', $user->id, $ext);
        Storage::disk('private')->put($path, file_get_contents($request->file('avatar')->getRealPath()));

        $user->update(['avatar_path' => $path]);

        return back()->with('success', 'Foto profil berhasil diperbarui.');
    }

    public function avatar(Request $request): StreamedResponse
    {
        $user = $request->user();

        abort_unless($user->avatar_path && Storage::disk('private')->exists($user->avatar_path), 404);

        return Storage::disk('private')->response($user->avatar_path);
    }

    public function enableTwoFactor(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']], [
            'password.required' => 'Masukkan password Anda untuk mengaktifkan 2FA.',
        ]);

        $request->user()->update(['two_factor_enabled' => true]);

        AuditLogger::log('user.two_factor_enabled', $request->user());

        return back()->with('success', 'Verifikasi dua faktor berhasil diaktifkan.');
    }

    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']], [
            'password.required' => 'Masukkan password Anda untuk menonaktifkan 2FA.',
        ]);

        $request->user()->forceFill([
            'two_factor_enabled' => false,
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ])->save();

        AuditLogger::log('user.two_factor_disabled', $request->user());

        return back()->with('success', 'Verifikasi dua faktor dinonaktifkan.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}

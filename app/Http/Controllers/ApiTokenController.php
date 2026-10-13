<?php

namespace App\Http\Controllers;

use App\Models\ApiToken;
use App\Services\ApiTokenService;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiTokenController extends Controller
{
    public function __construct(private readonly ApiTokenService $service) {}

    public function index(Request $request): View
    {
        $tokens = ApiToken::where('user_id', $request->user()->id)->latest()->get();

        return view('api-tokens.index', [
            'tokens' => $tokens,
            'plainToken' => session('plain_token'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ], [
            'name.required' => 'Nama token wajib diisi.',
        ]);

        $expiresAt = ! empty($data['expires_in_days']) ? now()->addDays((int) $data['expires_in_days']) : null;

        [$token, $plain] = $this->service->issue($request->user(), $data['name'], ['*'], $expiresAt);

        AuditLogger::log('api_token.created', $token, [], ['name' => $token->name]);

        return redirect()->route('api-tokens.index')->with('plain_token', $plain);
    }

    public function destroy(Request $request, ApiToken $apiToken): RedirectResponse
    {
        abort_unless($apiToken->user_id === $request->user()->id, 403);

        AuditLogger::log('api_token.revoked', $apiToken, ['name' => $apiToken->name], []);
        $apiToken->delete();

        return back()->with('success', 'Token berhasil dicabut.');
    }
}

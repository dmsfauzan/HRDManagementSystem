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
    private const ABILITY_PRESETS = [
        'full' => ['*'],
        'read_only' => ['profile:read', 'attendance:read', 'leave:read', 'announcement:read'],
        'attendance' => ['profile:read', 'attendance:read', 'attendance:write'],
        'leave' => ['profile:read', 'leave:read', 'leave:write'],
    ];

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
            'abilities_preset' => ['nullable', 'in:full,read_only,attendance,leave'],
        ], [
            'name.required' => 'Nama token wajib diisi.',
            'abilities_preset.in' => 'Scope token tidak valid.',
        ]);

        $expiresAt = ! empty($data['expires_in_days']) ? now()->addDays((int) $data['expires_in_days']) : null;
        $abilities = self::ABILITY_PRESETS[$data['abilities_preset'] ?? 'full'] ?? ['*'];

        [$token, $plain] = $this->service->issue($request->user(), $data['name'], $abilities, $expiresAt);

        AuditLogger::log('api_token.created', $token, [], ['name' => $token->name, 'abilities' => $abilities]);

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

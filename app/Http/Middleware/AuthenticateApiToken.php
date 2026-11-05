<?php

namespace App\Http\Middleware;

use App\Services\ApiTokenService;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function __construct(private readonly ApiTokenService $tokens) {}

    public function handle(Request $request, Closure $next, ?string $ability = null): Response
    {
        $plain = $this->normalizeToken($request->bearerToken());
        $token = $this->tokens->resolve($plain);

        if (! $token || ! $token->user || ! $token->user->is_active) {
            return ApiResponse::error('Unauthenticated.', 401);
        }

        if ($ability && ! $token->can($ability)) {
            return ApiResponse::error('Forbidden.', 403);
        }

        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('api_token', $token);

        return $next($request);
    }

    /**
     * Terima format token biasa maupun format ala Sanctum "id|token".
     */
    private function normalizeToken(?string $bearer): ?string
    {
        if (! $bearer) {
            return null;
        }

        if (str_contains($bearer, '|')) {
            $parts = explode('|', $bearer);
            $bearer = end($parts) ?: null;
        }

        return $bearer;
    }
}

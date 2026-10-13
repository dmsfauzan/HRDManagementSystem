<?php

namespace App\Http\Middleware;

use App\Services\ApiTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function __construct(private readonly ApiTokenService $tokens) {}

    public function handle(Request $request, Closure $next, ?string $ability = null): Response
    {
        $plain = $request->bearerToken();
        $token = $this->tokens->resolve($plain);

        if (! $token || ! $token->user || ! $token->user->is_active) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($ability && ! $token->can($ability)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('api_token', $token);

        return $next($request);
    }
}

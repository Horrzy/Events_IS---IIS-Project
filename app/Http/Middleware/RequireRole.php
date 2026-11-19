<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        $allowed = match ($role) {
            'admin' => $user->isAdmin(),
            'moderator' => $user->isModerator(),
            default => $user->role === $role,
        };

        if (!$allowed) {
            abort(403, 'K této stránce nemáte oprávnění.');
        }

        return $next($request);
    }
}

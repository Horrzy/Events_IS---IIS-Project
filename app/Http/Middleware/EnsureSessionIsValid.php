<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionIsValid
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = $request->user();

            if ($user === null || !$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('login')
                    ->with('error', 'Váš účet byl deaktivován.');
            }

            $timeoutSeconds = (int) config('session.lifetime', 30) * 60;
            $lastActivity = (int) $request->session()->get('last_activity_at', 0);

            if ($lastActivity > 0 && (now()->timestamp - $lastActivity) > $timeoutSeconds) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('login')
                    ->with('error', 'Byli jste automaticky odhlášeni kvůli nečinnosti.');
            }

            $request->session()->put('last_activity_at', now()->timestamp);
        }

        return $next($request);
    }
}

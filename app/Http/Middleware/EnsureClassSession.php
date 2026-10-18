<?php

namespace App\Http\Middleware;

use App\Support\SessionRequirement;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Kinder bleiben nur angemeldet, solange die Sitzung läuft, mit der sie sich
 * angemeldet haben. Endet sie (Zeit abgelaufen oder von einer Lehrkraft
 * beendet), werden sie abgemeldet.
 *
 * Children stay signed in only as long as the session they signed in with
 * is running. If it ends (time expired or ended by a teacher),
 * they are signed out.
 */
class EnsureClassSession
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && SessionRequirement::appliesTo($user)) {
            $running = SessionRequirement::runningFor($user);
            if (! $running || (int) $request->session()->get('class_session') !== $running->id) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                $text = __('Die Anmeldung für deine Klasse ist beendet. Du wurdest abgemeldet.');
                return $request->expectsJson()
                    ? response()->json(['message' => $text], 401)
                    : redirect()->route('login')->with('notice', $text);
            }
        }
        return $next($request);
    }
}

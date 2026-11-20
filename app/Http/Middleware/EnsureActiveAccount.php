<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Gesperrte Konten (Benutzer → „Konto aktiv“ aus) verlieren ihre laufende
 * Sitzung bei der nächsten Anfrage, nicht erst bei der nächsten Anmeldung
 * (Standard 09). Das Konto wird je Anfrage aus der Datenbank gelesen.
 *
 * Locked accounts (Users → “Account active” off) lose their running session
 * with the next request, not only at the next sign-in (standard 09). The
 * account is read from the database on every request.
 */
class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && ! $user->active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $text = __('Dieses Konto ist gesperrt. Frag den Admin.');

            return $request->expectsJson()
                ? response()->json(['message' => $text], 401)
                : redirect()->route('login')->withErrors(['username' => $text]);
        }

        return $next($request);
    }
}

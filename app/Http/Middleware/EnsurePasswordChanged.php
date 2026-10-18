<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Sperrt die Anwendung, bis Pflichtschritte erledigt sind:
 *  - Startpasswort ändern (neue Admins/Leiter)
 *  - Zwei-Faktor einrichten, wenn für die Rolle vorgeschrieben
 *
 * Locks the application until mandatory steps are done:
 *  - change the initial password (new admins/teachers)
 *  - set up two-factor if required for the role
 */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user?->must_change_password) {
            return redirect()->route('account')->with('notice', __('Bitte vergib zuerst ein eigenes Passwort.'));
        }
        if ($user && $user->twoFactorRequired() && ! $user->hasTwoFactor()) {
            return redirect()->route('account')->with('notice', __('Für deine Rolle ist die Zwei-Faktor-Anmeldung vorgeschrieben. Bitte richte sie jetzt ein.'));
        }
        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Session\Middleware\AuthenticateSession as LaravelAuthenticateSession;

/**
 * Passwortwechsel beendet die anderen Sitzungen: Laravel legt bei der
 * Anmeldung einen Hash des Passwort-Hashes in die Sitzung und vergleicht ihn
 * je Anfrage. Ändert sich das Passwort (selbst geändert, von einer Lehrkraft
 * bzw. einem Admin zurückgesetzt, Notzugang), passen die übrigen Sitzungen
 * nicht mehr und enden. Die eigene Sitzung beim eigenen Wechsel bekommt den
 * neuen Hash (AccountController::password). Anders als bei Laravel steht
 * danach auf der Anmeldeseite, warum man abgemeldet wurde.
 *
 * A password change ends the other sessions: at sign-in Laravel puts a hash
 * of the password hash into the session and compares it on every request.
 * If the password changes (changed by oneself, reset by a teacher or an
 * admin, emergency access), the other sessions no longer match and end. The
 * own session gets the new hash on one's own change
 * (AccountController::password). Unlike Laravel, the sign-in page then says
 * why one was signed out.
 */
class AuthenticateSession extends LaravelAuthenticateSession
{
    protected function logout($request)
    {
        $this->guard()->logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $text = __('Dein Passwort wurde geändert. Bitte melde dich neu an.');

        throw new HttpResponseException($request->expectsJson()
            ? response()->json(['message' => $text], 401)
            : redirect()->route('login')->with('notice', $text));
    }
}

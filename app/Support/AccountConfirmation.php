<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Bestätigung sicherheitsrelevanter Schritte (Konto-Seite, Sicherung zum
 * Herunterladen): eigene Konten mit ihrem Passwort, Schulkonten mit einem
 * aktuellen Code der Authenticator-App. Gebremst wie der Code der Anmeldung:
 * 5 Fehlversuche je Konto, dann 5 Minuten Pause; ein App-Code gilt nur einmal.
 * Alle Stellen teilen sich dieselbe Bremse.
 *
 * Confirmation of security-relevant steps (account page, backup download):
 * own accounts with their password, school accounts with a current code from
 * the authenticator app. Throttled like the sign-in code: 5 failed attempts
 * per account, then a 5-minute pause; an app code is valid only once. All
 * places share the same throttle.
 */
class AccountConfirmation
{
    /**
     * Prüft das Feld `password` der Anfrage. Zu viele Fehlversuche werfen eine
     * ValidationException mit der Wartezeit.
     *
     * Checks the `password` field of the request. Too many failed attempts throw
     * a ValidationException with the waiting time.
     */
    public static function passes(Request $request): bool
    {
        $request->validate(['password' => ['required', 'string', 'max:200']]);
        $user = $request->user();

        $key = 'account-confirm:' . $user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['password' => __('Zu viele Versuche. Bitte warte :n Sekunden.', [
                'n' => RateLimiter::availableIn($key),
            ])]);
        }

        $input = (string) $request->input('password');
        if ($user->isLocal()) {
            $ok = Hash::check($input, $user->password);
        } else {
            // Ein Code der Authenticator-App gilt nur einmal (last_step wie bei der Anmeldung)
            // A code from the authenticator app is valid only once (last_step as at sign-in)
            $step = $user->hasTwoFactor()
                ? Totp::verify($user->two_factor_secret, (string) preg_replace('/\s+/', '', $input), $user->two_factor_last_step)
                : null;
            $ok = $step !== null;
            if ($ok) {
                $user->forceFill(['two_factor_last_step' => $step])->save();
            }
        }

        $ok ? RateLimiter::clear($key) : RateLimiter::hit($key, 300);

        return $ok;
    }

    /**
     * Kann diese Person überhaupt bestätigen? Schulkonten brauchen dafür die
     * Zwei-Faktor-Anmeldung.
     *
     * Can this person confirm at all? School accounts need two-factor sign-in for it.
     */
    public static function possibleFor(\App\Models\User $user): bool
    {
        return $user->isLocal() || $user->hasTwoFactor();
    }
}

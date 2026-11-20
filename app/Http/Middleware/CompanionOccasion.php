<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Merkt sich nach einer abgeschickten Aktion, welcher Art sie war – damit
 * Cara danach benennt, was geschehen ist (docs/CARA.md): nach Löschen,
 * Passwort, Zwei-Faktor, Token, Sicherung, Abbrechen, Beenden oder Abmelden
 * schlicht „Erledigt.“ (serious), nach PUT/PATCH „Gespeichert.“ (saved),
 * sonst „Hat geklappt.“ (done). Nur wenn der Controller eine Erfolgsmeldung
 * (session 'ok') gesetzt hat; der Anlass gilt wie die Meldung genau für die
 * nächste Seite. Hat der Controller schon einen Anlass gesetzt, bleibt er.
 *
 * Remembers after a submitted action what kind it was – so that Cara then
 * names what happened (docs/CARA.md): after deleting, password, two-factor,
 * token, backup, cancelling, ending or signing out plainly "Done." (serious),
 * after PUT/PATCH "Saved." (saved), otherwise "That worked." (done). Only if
 * the controller set a success message (session 'ok'); like the message, the
 * occasion applies to exactly the next page. An occasion the controller has
 * already set is kept.
 */
class CompanionOccasion
{
    /**
     * Woran eine ernste Aktion zu erkennen ist (BilDaBooky: auch „close“ — Klassen-Sitzung beenden)
     *
     * What marks a serious action (BilDaBooky: also "close" — ending a class session)
     */
    private const SERIOUS = '/(destroy|delete|loesch|lösch|entfern|remove|passwor|password|2fa|mfa|totp|token|codes|logout|abmeld|sommer|summer|backup|sicherung|restore|wiederherst|leeren|clear|reset|beenden|close|abbrech|cancel)/u';

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // nur ein in DIESER Anfrage gesetzter Anlass zählt (nicht der Rest der vorigen Seite)
        // only an occasion set in THIS request counts (not the rest of the previous page)
        if (! $request->isMethod('GET') && $request->hasSession() && $request->session()->has('ok')
            && ! in_array('companion_occasion', (array) $request->session()->get('_flash.new', []), true)) {
            $request->session()->flash('companion_occasion', self::kind($request));
        }

        return $response;
    }

    /** Art der Aktion: serious, saved oder done / kind of action: serious, saved or done */
    public static function kind(Request $request): string
    {
        if (self::serious($request)) {
            return 'serious';
        }

        return $request->isMethod('PUT') || $request->isMethod('PATCH') ? 'saved' : 'done';
    }

    /** War die Anfrage eine ernste Aktion? / Was the request a serious action? */
    public static function serious(Request $request): bool
    {
        if ($request->isMethod('DELETE')) {
            return true;
        }
        $trace = strtolower(($request->route()?->getName() ?? '') . ' ' . $request->path());

        return (bool) preg_match(self::SERIOUS, $trace);
    }
}

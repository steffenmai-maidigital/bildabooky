<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Sprache bestimmen: 1. Wahl des Benutzers, 2. Wahl in dieser Sitzung/
 * diesem Browser, 3. Browsersprache — sonst Deutsch.
 *
 * Determine the language: 1. the user's choice, 2. choice in this session/
 * this browser, 3. browser language — otherwise German.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $available = array_keys(config('bildabooky.locales'));
        $cookie = $request->cookie('locale');
        if ($cookie === null && ($old = $request->cookie('sprache')) !== null) {
            // Cookie vor Standard 10 („sprache“): einmal übernehmen und löschen
            // cookie from before standard 10 ("sprache"): take it over once and delete it
            $cookie = $old;
            if (in_array($old, $available, true)) {
                Cookie::queue(Cookie::forever('locale', $old, httpOnly: true));
            }
            Cookie::queue(Cookie::forget('sprache'));
        }
        $wanted = $request->user()?->locale
            ?? $request->session()->get('locale')
            ?? $cookie;

        if (! in_array($wanted, $available, true)) {
            // getPreferredLanguage liefert den ersten Eintrag (de), wenn nichts passt
            // getPreferredLanguage returns the first entry (de) if nothing matches
            $wanted = substr((string) $request->getPreferredLanguage($available), 0, 2);
        }
        app()->setLocale(in_array($wanted, $available, true) ? $wanted : 'de');

        return $next($request);
    }
}

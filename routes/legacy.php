<?php

use App\Http\Controllers\HelpController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * Alte (deutsche) Adressen vor Standard 10 — leiten dauerhaft (301) auf die
 * neuen um, damit Lesezeichen, Anmeldekarten, QR-Codes und Hilfe-Links gültig
 * bleiben. Nur GET; Formulare (POST/PUT/PATCH/DELETE) kommen immer von der
 * neuen Seite. Die Abfrage (?…) wird unverändert mitgenommen; alte
 * Parameternamen (?klasse=, ?rolle=, ?suche=, ?seite=) lesen die Controller
 * weiterhin. Geladen von routes/web.php.
 *
 * Old (German) addresses from before standard 10 — redirect permanently (301)
 * to the new ones so that bookmarks, login cards, QR codes and help links
 * stay valid. GET only; forms (POST/PUT/PATCH/DELETE) always come from the
 * new page. The query (?…) is kept unchanged; the controllers still read the
 * old parameter names (?klasse=, ?rolle=, ?suche=, ?seite=). Loaded by
 * routes/web.php.
 */

/**
 * Ziel mit derselben Abfrage / Target with the same query
 */
$legacyRedirect = function (Request $request, string $target) {
    // unverändert (getQueryString() würde die Parameter umsortieren) / unchanged (getQueryString() would reorder)
    $query = (string) $request->server('QUERY_STRING', '');

    return redirect()->to($target . ($query !== '' ? '?' . $query : ''), 301);
};

// Alte Adresse => neue Adresse; {…} wird aus der alten Adresse übernommen.
// Feste Adressen stehen vor denen mit Platzhalter (/benutzer/karten vor /benutzer/{user}).
// Old address => new address; {…} is taken over from the old address.
// Fixed addresses come before those with a placeholder (/benutzer/karten before /benutzer/{user}).
$legacyPaths = [
    '/marke/logo'                            => '/brand/logo',
    '/impressum'                             => '/imprint',
    '/datenschutz'                           => '/privacy',
    '/sprache/{code}'                        => '/locale/{code}',
    '/anmelden'                              => '/login',
    '/anmelden/code'                         => '/login/code',
    '/anmelden/sitzungscode'                 => '/login/session-code',
    '/abmelden'                              => '/logout',
    '/konto'                                 => '/account',
    '/hilfe'                                 => '/help',
    '/vorschau'                              => '/previews',
    '/buecher/{book}'                        => '/books/{book}',
    '/buecher/{book}/bearbeiten'             => '/books/{book}/edit',
    '/buecher/{book}/drucken'                => '/books/{book}/print',
    '/buecher/{book}/datei'                  => '/books/{book}/download',
    '/buecher/{book}/protokoll'              => '/books/{book}/log',
    '/buecher/{book}/versionen'              => '/books/{book}/versions',
    '/buecher/{book}/stand'                  => '/books/{book}/state',
    '/medien/{media}'                        => '/media/{media}',
    '/benutzer'                              => '/users',
    '/benutzer/karten'                       => '/users/cards',
    '/benutzer/{user}'                       => '/users/{user}',
    '/klassen'                               => '/classes',
    '/klassen/{schoolClass}/mitmachen'       => '/classes/{schoolClass}/join',
    '/klassen/{schoolClass}/anmeldung'       => '/classes/{schoolClass}/sessions',
    '/klassen/{schoolClass}/bearbeiten'      => '/classes/{schoolClass}/edit',
    '/klassen/{schoolClass}/faecher'         => '/classes/{schoolClass}/edit',
    '/sicherung'                             => '/backup',
    '/einstellungen'                         => '/settings',
];

foreach ($legacyPaths as $old => $new) {
    Route::get($old, function (Request $request) use ($legacyRedirect, $new) {
        $target = preg_replace_callback('/\{(\w+)\}/', fn ($m) => rawurlencode((string) $request->route($m[1])), $new);

        return $legacyRedirect($request, $target);
    });
}

// Handbücher: Adresse und Kennung (benutzer → user …) / Manuals: address and slug (benutzer → user …)
Route::get('/hilfe/handbuch/{doc}', function (Request $request, string $doc) use ($legacyRedirect) {
    $doc = HelpController::LEGACY_DOCS[$doc] ?? $doc;

    return $legacyRedirect($request, '/help/manual/' . rawurlencode($doc));
});
// eigener Platzhaltername: dieselbe Adresse wie help.doc würde die Route ersetzen
// own placeholder name: the same URI as help.doc would replace the route
Route::get('/help/manual/{legacyDoc}', fn (Request $request, string $legacyDoc) => $legacyRedirect($request, '/help/manual/' . HelpController::LEGACY_DOCS[$legacyDoc]))
    ->where('legacyDoc', implode('|', array_keys(HelpController::LEGACY_DOCS)));

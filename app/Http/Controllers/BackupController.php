<?php

namespace App\Http\Controllers;

use App\Support\AccountConfirmation;
use App\Support\Backup;
use Illuminate\Http\Request;

/**
 * Sicherung zum Herunterladen (Datenbank + Medien) – nur per Formular mit
 * Bestätigung durch das eigene Passwort bzw. bei Schulkonten einen Code der
 * Authenticator-App (gleiche Bremse wie auf der Konto-Seite). Der App-Schlüssel
 * kommt nicht mit: Er bleibt auf dem Server und steckt nur in den Sicherungen
 * dort (nächtlich, vor dem Löschen einer Klasse, vor dem Sommerferienmodus).
 *
 * Backup for download (database + media) – only via a form with confirmation
 * by one's own password or, for school accounts, a code from the authenticator
 * app (same throttle as on the account page). The app key does not come along:
 * it stays on the server and is only part of the backups there (nightly,
 * before deleting a class, before the summer holiday mode).
 */
class BackupController extends Controller
{
    /**
     * Alte Adresse per GET: keine Sicherung, sondern zur Stelle mit dem Formular
     *
     * Old address via GET: no backup, but to the place with the form
     */
    public function show()
    {
        return redirect()->to(route('classes.index') . '#backup');
    }

    public function download(Request $request)
    {
        if (! AccountConfirmation::possibleFor($request->user())) {
            return redirect()->to(route('classes.index') . '#backup')
                ->withErrors(['password' => __('Für die Sicherung zum Herunterladen brauchst du die Zwei-Faktor-Anmeldung. Richte sie unter „Konto“ ein.')]);
        }
        if (! AccountConfirmation::passes($request)) {
            return redirect()->to(route('classes.index') . '#backup')
                ->withErrors(['password' => $request->user()->isLocal() ? __('Das Passwort stimmt nicht.') : __('Der Code stimmt nicht.')]);
        }

        $path = Backup::create(storage_path('app/backup'), null, withKey: false);
        return response()->download($path)->deleteFileAfterSend();
    }
}

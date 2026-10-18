<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\ErrorLog;
use App\Support\License;
use App\Support\Directory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Hilfe-Seite nach Standard 03: Kurzanleitung, System, Lizenz, Handbücher,
 * Änderungshistorie, letzte Fehler (Admins), Support.
 *
 * Help page according to Standard 03: quick guide, system, licence, manuals,
 * change history, recent errors (admins), support.
 */
class HelpController extends Controller
{
    /**
     * Handbücher aus docs/ — Schlüssel => [Titel, Datei, nur Admin, Kurzbeschreibung]
     *
     * Manuals from docs/ — key => [title, file, admin only, short description]
     */
    public const DOCS = [
        'user'         => ['Benutzerhandbuch', 'docs/USER-GUIDE.md', false, 'Anmelden, Bibliothek, Editor, Teilen, Mitnehmen'],
        'admin'        => ['Administratorhandbuch', 'docs/ADMIN-GUIDE.md', true, 'Einrichtung, Einstellungen, Benutzer, Betrieb'],
        'architecture' => ['Systemarchitektur', 'docs/ARCHITECTURE.md', true, 'Aufbau, Datenmodell, Abläufe'],
        'security'     => ['Sicherheitsarchitektur', 'docs/SECURITY.md', true, 'Anmeldung, Rechte, Schutzmaßnahmen'],
        'overview'     => ['Überblick: Module & Abläufe', 'docs/DOCUMENTATION.md', false, 'Alle Module auf einen Blick'],
        'changelog'    => ['Änderungshistorie (Changelog)', 'CHANGELOG.md', false, 'Was ist in welcher Version neu'],
    ];

    /**
     * Alte Handbuch-Adressen (vor Standard 10) → neue; leiten dauerhaft um
     *
     * Old manual addresses (before standard 10) → new ones; redirect permanently
     */
    public const LEGACY_DOCS = [
        'benutzer'    => 'user',
        'architektur' => 'architecture',
        'sicherheit'  => 'security',
        'ueberblick'  => 'overview',
    ];

    public function show(Request $request)
    {
        $user = $request->user();

        return view('help', [
            'user'           => $user,
            'license'        => License::current(),
            'licenseCompany' => Setting::get('license_company'),
            'rawKey'         => Setting::get('license_key'),
            'directory'      => Directory::enabled(),
            'docs'           => self::DOCS,
            'isAdmin'        => $user->isAdmin(),
            'recentErrors'   => $user->isAdmin() ? ErrorLog::recent() : null,
        ]);
    }

    /** Ein Handbuch als Seite (Markdown, HTML wird maskiert) / A manual as a page (Markdown, HTML is escaped) */
    public function doc(Request $request, string $doc)
    {
        abort_unless(isset(self::DOCS[$doc]), 404);
        [$title, $file, $adminOnly] = self::DOCS[$doc];
        abort_if($adminOnly && ! $request->user()->isAdmin(), 403);

        $path = base_path($file);
        abort_unless(is_file($path), 404);
        $markdown = (string) file_get_contents($path);
        try {
            $html = Str::markdown($markdown, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
        } catch (\Throwable) {
            $html = '<pre style="white-space:pre-wrap">' . e($markdown) . '</pre>';
        }

        return view('help-doc', ['title' => $title, 'html' => $html, 'doc' => $doc, 'docs' => self::DOCS, 'isAdmin' => $request->user()->isAdmin()]);
    }

    /** Admin: Anwendungsprotokoll leeren / Admin: clear the application log */
    public function clearLog()
    {
        ErrorLog::clear();

        return redirect()->route('help')->with('ok', __('Das Protokoll wurde geleert.'));
    }
}

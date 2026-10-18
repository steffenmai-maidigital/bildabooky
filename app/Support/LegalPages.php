<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Impressum und Datenschutzerklärung: vom Admin ein- und ausschaltbar,
 * entweder als eigener Text oder als Link auf eine vorhandene Seite
 * (z. B. die Schulhomepage). Öffentlich, auch vor der Anmeldung.
 *
 * Imprint (legal notice) and privacy policy: can be switched on and off by the admin,
 * either as own text or as a link to an existing page
 * (e.g. the school website). Public, even before sign-in.
 */
class LegalPages
{
    public const TYPES = ['imprint', 'privacy'];
    public const MAX = 30000;

    public static function enabled(string $type): bool
    {
        if (Setting::get($type . '_enabled', '0') !== '1') {
            return false;
        }
        return self::link($type) !== null || trim(self::text($type)) !== '';
    }

    public static function text(string $type): string
    {
        return (string) Setting::get($type . '_text', '');
    }

    /**
     * Externe Adresse, wenn die Seite als Link eingestellt ist (nur http/https).
     *
     * External address if the page is set up as a link (http/https only).
     */
    public static function link(string $type): ?string
    {
        if (Setting::get($type . '_type', 'text') !== 'link') {
            return null;
        }
        $url = (string) Setting::get($type . '_url', '');
        return preg_match('#^https?://[^\s<>"]+$#i', $url) ? $url : null;
    }

    public static function title(string $type): string
    {
        return $type === 'imprint' ? __('Impressum') : __('Datenschutz');
    }

    /** Adresse für Links in Fußzeile und Anmeldeseite / Address for links in the footer and on the sign-in page */
    public static function url(string $type): string
    {
        return self::link($type) ?? route($type);
    }

    /** Sichtbare Seiten als [type => [title, url, external]] / Visible pages as [type => [title, url, external]] */
    public static function links(): array
    {
        $out = [];
        foreach (self::TYPES as $type) {
            if (self::enabled($type)) {
                $out[$type] = ['title' => self::title($type), 'url' => self::url($type), 'external' => self::link($type) !== null];
            }
        }
        return $out;
    }

    /**
     * Klartext → sicheres HTML: Zeilen mit „# “ werden Überschriften,
     * Leerzeilen trennen Absätze, Adressen und E-Mails werden zu Links.
     * Alles andere wird maskiert — der Admin kann kein HTML einschleusen.
     *
     * Plain text → safe HTML: lines starting with “# ” become headings,
     * blank lines separate paragraphs, addresses and e-mails become links.
     * Everything else is escaped — the admin cannot inject HTML.
     */
    public static function html(string $text): string
    {
        $blocks = preg_split("/\R{2,}/", trim(str_replace("\r", '', $text))) ?: [];
        $html = '';
        foreach ($blocks as $block) {
            $rows = explode("\n", $block);
            $paragraph = [];
            $done = function () use (&$paragraph, &$html) {
                if ($paragraph) {
                    $html .= '<p>' . implode('<br>', array_map([self::class, 'line'], $paragraph)) . "</p>\n";
                    $paragraph = [];
                }
            };
            foreach ($rows as $z) {
                if (preg_match('/^(#{1,3})\s+(.+)$/', $z, $m)) {
                    $done();
                    $h = strlen($m[1]) === 1 ? 'h2' : 'h3';
                    $html .= "<$h>" . e($m[2]) . "</$h>\n";
                } else {
                    $paragraph[] = $z;
                }
            }
            $done();
        }
        return $html;
    }

    private static function line(string $z): string
    {
        $parts = preg_split('#(https?://[^\s<>"]+[^\s<>".,;:!?)]|[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,})#', $z, -1, PREG_SPLIT_DELIM_CAPTURE);
        $out = '';
        foreach ($parts as $i => $t) {
            if ($i % 2 === 0) {
                $out .= e($t);
            } elseif (str_contains($t, '@') && ! str_starts_with($t, 'http')) {
                $out .= '<a href="mailto:' . e($t) . '">' . e($t) . '</a>';
            } else {
                $out .= '<a href="' . e($t) . '" target="_blank" rel="noopener">' . e($t) . '</a>';
            }
        }
        return $out;
    }

    /**
     * Vorlage für die Datenschutzerklärung — beschreibt, was BilDaBooky tatsächlich tut
     *
     * Template for the privacy policy — describes what BilDaBooky actually does
     */
    public static function privacyTemplate(): string
    {
        $school = Branding::school();
        return <<<TXT
# Datenschutzerklärung für BilDaBooky

Diese Erklärung gilt für die Buch-App BilDaBooky der Schule „{$school}“. Bitte vor der Veröffentlichung prüfen und ergänzen — die Angaben in [eckigen Klammern] müssen ersetzt werden.

## Verantwortlich
[Name und Anschrift der Schule, vertreten durch die Schulleitung]
[Telefon, E-Mail]

## Datenschutzbeauftragte Person
[Name bzw. Stelle, E-Mail]

## Wofür wir die App nutzen
Die Kinder gestalten im Unterricht eigene Bücher mit Text, Fotos, Zeichnungen und Tonaufnahmen. Lehrkräfte stellen Vorlagen bereit und sehen die Bücher ihrer Klasse.

## Welche Daten verarbeitet werden
- Benutzerkonto: Benutzername, Vor- und Nachname, Klasse, Rolle, Passwort (nur verschlüsselt als Hash gespeichert), freiwillig ein zweiter Faktor für die Anmeldung
- Inhalte der Bücher: Texte, Fotos, Zeichnungen und Tonaufnahmen, die die Kinder selbst einfügen
- Änderungsprotokoll: wer wann ein gemeinsames Buch geändert hat (nur für Lehrkraft und Admin sichtbar)
- Technische Daten: Fehlermeldungen (höchstens 30 Tage), Sitzungs-Cookie für die Anmeldung

## Wo die Daten liegen
Alle Daten liegen ausschließlich auf dem Server der Schule [Ort/Betreiber des Servers]. BilDaBooky lädt keine Schriften, Skripte oder Bilder von fremden Servern, verwendet keine Werbung, keine Analyse- oder Tracking-Dienste und gibt keine Daten an Dritte weiter.

## Cookies
Es wird nur ein technisch notwendiges Cookie für die Anmeldung gesetzt (Sitzung und Schutz vor gefälschten Anfragen). Es wird beim Abmelden bzw. nach Ablauf der Sitzung gelöscht.

## Rechtsgrundlage
[z. B. Art. 6 Abs. 1 lit. e DSGVO in Verbindung mit dem Schulgesetz des Landes; für Fotos und Tonaufnahmen gegebenenfalls die Einwilligung der Erziehungsberechtigten nach Art. 6 Abs. 1 lit. a DSGVO]

## Wie lange die Daten gespeichert werden
Die Bücher bleiben gespeichert, solange das Kind die App nutzt. Zum Schuljahresende können alle Schülerkonten mit ihren Büchern gelöscht werden; die Kinder können ihre Bücher vorher als Datei mit nach Hause nehmen. Sicherungskopien werden nach [14] Tagen gelöscht.

## Ihre Rechte
Sie haben das Recht auf Auskunft, Berichtigung, Löschung und Einschränkung der Verarbeitung, auf Datenübertragbarkeit sowie auf Widerspruch und auf Widerruf einer Einwilligung. Wenden Sie sich dafür an die Schule. Außerdem können Sie sich bei der Datenschutz-Aufsichtsbehörde beschweren: [zuständige Landesbehörde].

Stand: [Datum]
TXT;
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Englische Namen (Standard 10): Spalten, gespeicherte Werte, Einstellungs-
 * schlüssel und JSON-Inhalte (Änderungsprotokoll, Seiten, letzter
 * Sommerferienlauf) bekommen ihre englischen Namen. Idempotent — läuft
 * gefahrlos auf einer schon umgestellten Datenbank und auf einer alten,
 * zurückgespielten Sicherung. Die Werte stehen hier bewusst noch einmal
 * ausgeschrieben, damit die Migration auch nach späteren Änderungen am Code
 * gleich arbeitet. Die Spaltenstandards der alten Migrationen (role
 * „ersteller“, kind „eigen“, format „hoch“, sharing „privat“) bleiben stehen
 * (sonst müsste SQLite die Tabellen neu bauen); der Code schreibt diese
 * Spalten immer ausdrücklich.
 *
 * English names (standard 10): columns, stored values, settings keys and JSON
 * contents (change log, pages, last summer holiday run) get their English
 * names. Idempotent — runs safely on a database that is already converted and
 * on an old restored backup. The values are spelled out here on purpose so
 * that the migration keeps working the same after later code changes. The
 * column defaults of the old migrations (role "ersteller", kind "eigen",
 * format "hoch", sharing "privat") are left alone (SQLite would have to
 * rebuild the tables); the code always writes these columns explicitly.
 */
return new class extends Migration
{
    /** Spalten alt => neu je Tabelle / columns old => new per table */
    private const COLUMNS = [
        'users'                    => ['fuchs' => 'fox'],
        'books'                    => ['freigabe' => 'sharing'],
        'book_logs'                => ['seite' => 'page', 'aktion' => 'action'],
        'class_leader'             => ['klassenlehrkraft' => 'is_class_teacher'],
        'class_session_extensions' => ['alt_ende' => 'old_end', 'neu_ende' => 'new_end'],
    ];

    /** Einstellungs-Schlüssel alt => neu / settings keys old => new */
    private const SETTINGS = [
        'fuchs_aktiv'            => 'fox_enabled',
        'ldap_aktiv'             => 'ldap_enabled',
        'ldap_verschluesselung'  => 'ldap_encryption',
        'ldap_bind_passwort'     => 'ldap_bind_password',
        'ldap_bind_vorlage'      => 'ldap_bind_template',
        'ldap_benutzer_attribut' => 'ldap_user_attribute',
        'ldap_benutzer_filter'   => 'ldap_user_filter',
        'ldap_zertifikat'        => 'ldap_certificate',
        'ldap_gruppe_admin'      => 'ldap_group_admin',
        'ldap_gruppe_leiter'     => 'ldap_group_teacher',
        'mfa_gruppen_pflicht'    => 'mfa_required_groups',
        'mfa_leiter'             => 'mfa_teacher',
        'impressum_aktiv'        => 'imprint_enabled',
        'impressum_art'          => 'imprint_type',
        'impressum_text'         => 'imprint_text',
        'impressum_link'         => 'imprint_url',
        'datenschutz_aktiv'      => 'privacy_enabled',
        'datenschutz_art'        => 'privacy_type',
        'datenschutz_text'       => 'privacy_text',
        'datenschutz_link'       => 'privacy_url',
        'sommer_aktiv'           => 'summer_enabled',
        'sommer_datum'           => 'summer_date',
        'sommer_klassenbuecher'  => 'summer_class_books',
        'sommer_klassen'         => 'summer_classes',
        'sommer_jaehrlich'       => 'summer_yearly',
        'sommer_letzte'          => 'summer_last',
        'sitzungspflicht'        => 'session_required',
        'lehrkraefte_eintragen'  => 'teachers_can_join',
    ];

    /** Zwei-Faktor-Stufen (users.mfa_policy, Einstellungen mfa_<group>) / two-factor levels */
    private const MFA_LEVELS = ['pflicht' => 'required', 'aus' => 'off'];

    /** users.role */
    private const ROLES = ['leiter' => 'teacher', 'ersteller' => 'pupil'];

    /** books.kind */
    private const KINDS = ['eigen' => 'own', 'klasse' => 'class', 'vorlage' => 'template'];

    /** books.sharing */
    private const SHARING = ['privat' => 'private', 'klasse' => 'class', 'lehrkraft' => 'teacher'];

    /** books.format */
    private const FORMATS = ['hoch' => 'portrait', 'quadrat' => 'square', 'quer' => 'landscape'];

    /** media.kind */
    private const MEDIA = ['bild' => 'image', 'ton' => 'audio'];

    /** Schriften und Formen in pages.elements / fonts and shapes in pages.elements */
    private const FONTS = ['rund' => 'rounded', 'dick' => 'heavy'];
    private const SHAPES = ['rechteck' => 'rectangle', 'rund' => 'rounded', 'kreis' => 'circle'];

    /** book_logs.action */
    private const ACTIONS = [
        'erstellt' => 'created', 'kopie' => 'copy', 'titel' => 'title', 'freigabe' => 'sharing', 'klasse' => 'class',
        'fach' => 'subject', 'seite_neu' => 'page_added', 'seite_weg' => 'page_removed', 'reihenfolge' => 'order',
        'abgegeben' => 'submitted', 'abgabe_zurueck' => 'submission_returned', 'zurueckgegeben' => 'returned', 'seite' => 'page',
    ];

    /** Schlüssel in book_logs.details / keys in book_logs.details */
    private const DETAIL_KEYS = ['alt' => 'old', 'neu' => 'new', 'notiz' => 'note', 'von' => 'from', 'vorlage' => 'template', 'art' => 'type'];

    /** Schlüssel des Seiten-Diffs (Aktion page) / keys of the page diff (action page) */
    private const DIFF_KEYS = ['neu' => 'added', 'weg' => 'removed', 'inhalt' => 'content', 'lage' => 'layout', 'texte' => 'texts', 'hintergrund' => 'background'];

    /** Einstellung summer_last (JSON) / setting summer_last (JSON) */
    private const SUMMER_KEYS = [
        'kinder' => 'pupils', 'buecher' => 'books', 'medien' => 'media', 'klassen' => 'classes',
        'fuer' => 'for', 'am' => 'at', 'ausloeser' => 'trigger', 'sicherung' => 'backup',
    ];
    private const SUMMER_TRIGGERS = ['Zeitplan' => 'schedule', 'Befehl' => 'command', 'automatisch' => 'automatic'];

    public function up(): void
    {
        $this->convert(false);
    }

    /**
     * Rückweg (so weit möglich): alte Namen und Werte wiederherstellen
     *
     * Way back (as far as possible): restore the old names and values
     */
    public function down(): void
    {
        $this->convert(true);
    }

    private function convert(bool $back): void
    {
        $flip = fn (array $map) => $back ? array_flip($map) : $map;

        // ---- Spalten / Columns ----------------------------------------------
        foreach (self::COLUMNS as $table => $columns) {
            foreach ($flip($columns) as $from => $to) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, $from) && ! Schema::hasColumn($table, $to)) {
                    Schema::table($table, fn (Blueprint $t) => $t->renameColumn($from, $to));
                }
            }
        }

        // ---- Einfache Werte / Plain values ------------------------------------
        $this->values('users', 'role', $flip(self::ROLES));
        $this->values('users', 'mfa_policy', $flip(self::MFA_LEVELS));
        $this->values('books', 'kind', $flip(self::KINDS));
        $this->values('books', $back ? 'freigabe' : 'sharing', $flip(self::SHARING));
        $this->values('books', 'format', $flip(self::FORMATS));
        $this->values('media', 'kind', $flip(self::MEDIA));

        // ---- Einstellungen / Settings ---------------------------------------
        if (Schema::hasTable('settings')) {
            foreach ($flip(self::SETTINGS) as $old => $new) {
                $this->renameSetting($old, $new);
            }
            // Stufen je Gruppe / levels per group
            $levels = $flip(self::MFA_LEVELS);
            foreach (DB::table('settings')->where('key', 'like', 'mfa_%')->get() as $row) {
                if (in_array($row->key, ['mfa_required_groups', 'mfa_gruppen_pflicht'], true)) {
                    continue;
                }
                if (isset($levels[(string) $row->value])) {
                    DB::table('settings')->where('key', $row->key)->update(['value' => $levels[$row->value]]);
                }
            }
            // letzter Sommerferienlauf (JSON) / last summer holiday run (JSON)
            $key = $back ? 'sommer_letzte' : 'summer_last';
            $json = DB::table('settings')->where('key', $key)->value('value');
            $data = $json ? json_decode((string) $json, true) : null;
            if (is_array($data)) {
                $data = $this->renameKeys($data, $flip(self::SUMMER_KEYS));
                $triggerKey = $back ? 'ausloeser' : 'trigger';
                if (isset($data[$triggerKey])) {
                    $data[$triggerKey] = $flip(self::SUMMER_TRIGGERS)[$data[$triggerKey]] ?? $data[$triggerKey];
                }
                DB::table('settings')->where('key', $key)->update(['value' => json_encode($data)]);
            }
        }

        // ---- Änderungsprotokoll / Change log ------------------------------------
        $actionColumn = $back ? 'aktion' : 'action';
        if (Schema::hasTable('book_logs') && Schema::hasColumn('book_logs', $actionColumn)) {
            $this->values('book_logs', $actionColumn, $flip(self::ACTIONS));
            DB::table('book_logs')->whereNotNull('details')->orderBy('id')->chunkById(500, function ($rows) use ($actionColumn, $back, $flip) {
                foreach ($rows as $row) {
                    $details = json_decode((string) $row->details, true);
                    if (! is_array($details) || ! $details) {
                        continue;
                    }
                    $new = $this->convertDetails($row->{$actionColumn}, $details, $back, $flip);
                    if ($new !== $details) {
                        DB::table('book_logs')->where('id', $row->id)->update(['details' => json_encode($new, JSON_UNESCAPED_UNICODE)]);
                    }
                }
            });
        }

        // ---- Seiten: Schriften und Formen / Pages: fonts and shapes ----------------
        if (Schema::hasTable('pages') && Schema::hasColumn('pages', 'elements')) {
            $fonts = $flip(self::FONTS);
            $shapes = $flip(self::SHAPES);
            DB::table('pages')->whereNotNull('elements')->orderBy('id')->chunkById(200, function ($rows) use ($fonts, $shapes) {
                foreach ($rows as $row) {
                    $elements = json_decode((string) $row->elements, true);
                    if (! is_array($elements)) {
                        continue;
                    }
                    $changed = false;
                    foreach ($elements as &$e) {
                        if (! is_array($e)) {
                            continue;
                        }
                        if (($e['type'] ?? null) === 'text' && is_string($e['font'] ?? null) && isset($fonts[$e['font']])) {
                            $e['font'] = $fonts[$e['font']];
                            $changed = true;
                        }
                        if (($e['type'] ?? null) === 'shape' && is_string($e['shape'] ?? null) && isset($shapes[$e['shape']])) {
                            $e['shape'] = $shapes[$e['shape']];
                            $changed = true;
                        }
                    }
                    unset($e);
                    if ($changed) {
                        DB::table('pages')->where('id', $row->id)->update(['elements' => json_encode($elements, JSON_UNESCAPED_UNICODE)]);
                    }
                }
            });
        }
    }

    /**
     * Inhalt von book_logs.details je Aktion umstellen (Aktion ist schon im Zielnamen)
     *
     * Convert the content of book_logs.details per action (the action already has its target name)
     */
    private function convertDetails(?string $action, array $d, bool $back, callable $flip): array
    {
        $page = $back ? 'seite' : 'page';
        if ($action === $page) {
            return $this->renameKeys($d, $flip(self::DIFF_KEYS));
        }
        $d = $this->renameKeys($d, $flip(self::DETAIL_KEYS));
        $values = match ($action) {
            $back ? 'freigabe' : 'sharing' => $flip(self::SHARING),
            'format'                       => $flip(self::FORMATS),
            $back ? 'erstellt' : 'created' => $flip(self::KINDS),
            default                        => [],
        };
        foreach (['old', 'new', 'type', 'alt', 'neu', 'art'] as $k) {
            if (isset($d[$k]) && is_string($d[$k]) && isset($values[$d[$k]])) {
                $d[$k] = $values[$d[$k]];
            }
        }

        return $d;
    }

    /** Schlüssel eines Arrays umbenennen (vorhandene neue Schlüssel gewinnen) / Rename array keys (existing new keys win) */
    private function renameKeys(array $data, array $map): array
    {
        foreach ($map as $old => $new) {
            if (array_key_exists($old, $data)) {
                if (! array_key_exists($new, $data)) {
                    $data[$new] = $data[$old];
                }
                unset($data[$old]);
            }
        }

        return $data;
    }

    /** Gespeicherte Werte einer Spalte übersetzen / Translate the stored values of a column */
    private function values(string $table, string $column, array $map): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }
        foreach ($map as $old => $new) {
            DB::table($table)->where($column, $old)->update([$column => $new]);
        }
    }

    /**
     * Einstellung umbenennen; gibt es den neuen Schlüssel schon, gilt er und der alte entfällt
     *
     * Rename a setting; if the new key exists already it wins and the old one is removed
     */
    private function renameSetting(string $old, string $new): void
    {
        if (! DB::table('settings')->where('key', $old)->exists()) {
            return;
        }
        if (DB::table('settings')->where('key', $new)->exists()) {
            DB::table('settings')->where('key', $old)->delete();

            return;
        }
        DB::table('settings')->where('key', $old)->update(['key' => $new]);
    }
};

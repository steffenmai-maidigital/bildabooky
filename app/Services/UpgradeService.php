<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sicheres Update der Anwendung beim Container-Start:
 *
 *  1. Versionsabgleich (installiert vs. Paket) – ein Downgrade wird
 *     abgelehnt, bevor irgendetwas verändert wird
 *  2. Automatisches Backup der SQLite-Datenbank VOR jeder Änderung
 *  3. Schema-Migrationen (php artisan migrate)
 *  4. Versionsspezifische Konvertierungen (alte Stände/Majors), der Reihe
 *     nach von der installierten bis zur Paket-Version
 *  5. Neue Version/Build in der Datenbank vermerken + protokollieren
 *
 * Alle Schritte sind idempotent – der Lauf bei jedem Start ist gewollt.
 *
 * Safe update of the application at container start:
 *
 *  1. Version check (installed vs. package) – a downgrade is
 *     rejected before anything is changed
 *  2. Automatic backup of the SQLite database BEFORE any change
 *  3. Schema migrations (php artisan migrate)
 *  4. Version-specific conversions (old states/majors), in order
 *     from the installed up to the package version
 *  5. Record the new version/build in the database + log it
 *
 * All steps are idempotent – running on every start is intended.
 */
class UpgradeService
{
    /**
     * Konvertierungen je Zielversion (Schema JJMMTTNN), aufsteigend sortiert.
     * Wird eine Installation mit älterem Stand aktualisiert, laufen alle
     * Schritte zwischen installierter und Paket-Version genau einmal.
     *
     * Beispiel:
     *   '26110101' => 'umbauAuf26110101',
     *
     * Conversions per target version (scheme YYMMDDNN), sorted ascending.
     * When an installation with an older state is updated, all
     * steps between the installed and the package version run exactly once.
     *
     * Example:
     *   '26110101' => 'umbauAuf26110101',
     */
    private const STEPS = [];

    public function run(): void
    {
        $packageVersion = (string) config('version.number');
        $packageBuild   = (string) config('version.build');

        try {
            $installedVersion = Setting::get('app_version') ?: '0.0';
            $installedBuild   = Setting::get('app_build') ?: '';
        } catch (\Throwable) {
            // Erstinstallation: noch keine settings-Tabelle vorhanden
            // First installation: no settings table yet
            $installedVersion = '0.0';
            $installedBuild   = '';
        }

        // ---- 1. Downgrade-Schutz / Downgrade protection --------------
        if (version_compare($installedVersion, $packageVersion, '>')) {
            throw new \RuntimeException(
                'Downgrade abgelehnt: Die Datenbank stammt von Version ' . $installedVersion
                . ', dieses Abbild hat nur Version ' . $packageVersion . '. Es wurde nichts verändert. '
                . 'Entweder das Abbild mit Version ' . $installedVersion . ' oder neuer starten, '
                . 'oder vorher die Sicherung aus /data/backups zurückspielen, die zu Version ' . $packageVersion . ' passt.'
            );
        }

        $isUpdate = $installedVersion !== $packageVersion || $installedBuild !== $packageBuild;
        $pending  = $this->pendingMigrations();

        // ---- 2. Backup vor jeder Änderung / Backup before any change ----
        // (auch bei bestehenden Installationen ohne Versionsvermerk)
        // (also for existing installations without a version record)
        if (($isUpdate || $pending) && $this->hasData()) {
            $this->backupDatabase($installedVersion, $installedBuild);
        }

        // ---- 3. Schema-Migrationen / Schema migrations ---------------
        Artisan::call('migrate', ['--force' => true]);

        // ---- 4. Versionsspezifische Konvertierungen / Version-specific conversions ----
        foreach (self::STEPS as $target => $method) {
            if (version_compare($installedVersion, $target, '<')
                && version_compare($packageVersion, $target, '>=')) {
                Log::info("Upgrade-Konvertierung auf {$target} …");
                $this->{$method}();
            }
        }

        // ---- 5. Version vermerken / Record the version ---------------
        if ($isUpdate) {
            Setting::put('app_version', $packageVersion);
            Setting::put('app_build', $packageBuild);
            Setting::put('app_updated_at', now()->toDateTimeString());

            Log::info($installedBuild === ''
                    ? 'System installiert: Version ' . $packageVersion . ', Build ' . $packageBuild
                    : 'System aktualisiert: Version ' . $installedVersion . ' (Build ' . $installedBuild . ') → Version ' . $packageVersion . ' (Build ' . $packageBuild . ')');
        }
    }

    /**
     * Gibt es schon Daten, die vor dem Update gesichert werden müssen?
     *
     * Is there already data that must be backed up before the update?
     */
    private function hasData(): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable('migrations');
        } catch (\Throwable) {
            return false;
        }
    }

    /** Gibt es Migrationen, die noch nicht gelaufen sind? / Are there migrations that have not run yet? */
    private function pendingMigrations(): bool
    {
        try {
            if (! DB::getSchemaBuilder()->hasTable('migrations')) {
                return true;
            }
            $ran = DB::table('migrations')->pluck('migration')->all();
            foreach (glob(database_path('migrations/*.php')) as $file) {
                if (! in_array(basename($file, '.php'), $ran, true)) {
                    return true;
                }
            }
        } catch (\Throwable) {
            return true;
        }

        return false;
    }

    /**
     * SQLite-Datei nach /data/backups sichern, die letzten 10 behalten
     *
     * Back up the SQLite file to /data/backups, keep the last 10
     */
    private function backupDatabase(string $version, string $build): void
    {
        $db = (string) config('database.connections.sqlite.database');
        if (! is_file($db)) {
            return;
        }

        $dir = dirname($db) . '/backups';
        @mkdir($dir, 0775, true);

        $target = sprintf('%s/backup_v%s_build%s_%s.sqlite', $dir, $version, $build ?: '0', now()->format('Ymd_His'));

        // Konsistente Kopie über die SQLite-Backup-API (statt File-Copy)
        // Consistent copy via the SQLite backup API (instead of a file copy)
        try {
            DB::statement('VACUUM INTO ' . DB::getPdo()->quote($target));
        } catch (\Throwable $e) {
            copy($db, $target); // Fallback
        }

        Log::info('Datenbank-Backup vor Update: ' . $target);

        // Aufräumen: nur die 10 neuesten Backups behalten
        // Clean-up: keep only the 10 newest backups
        $files = glob($dir . '/backup_*.sqlite') ?: [];
        rsort($files);
        foreach (array_slice($files, 10) as $old) {
            @unlink($old);
        }
    }
}

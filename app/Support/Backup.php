<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use ZipArchive;

/**
 * Sicherung: Datenbank (konsistente Kopie per VACUUM INTO) + alle Medien als ZIP
 *
 * Backup: database (consistent copy via VACUUM INTO) + all media as a ZIP
 */
class Backup
{
    /**
     * Sicherung anlegen. $withKey: den App-Schlüssel (`.app_key`) mit einpacken –
     * ja bei den Sicherungen auf dem Server (nächtlich, vor dem Löschen einer
     * Klasse, vor dem Sommerferienmodus), nein beim Download im Browser: Wer die
     * Datei bekommt, soll damit nicht auch die Zwei-Faktor-Geheimnisse und die
     * Passwörter der Anmeldekarten entschlüsseln können.
     *
     * Create a backup. $withKey: pack the app key (`.app_key`) as well – yes for
     * the backups on the server (nightly, before deleting a class, before the
     * summer holiday mode), no for the download in the browser: whoever gets the
     * file must not also be able to decrypt the two-factor secrets and the
     * passwords of the login cards with it.
     */
    public static function create(string $targetDir, ?string $suffix = null, bool $withKey = true): string
    {
        @mkdir($targetDir, 0775, true);
        $stamp = now()->format('Y-m-d_His');
        $zipPath = rtrim($targetDir, '/') . "/bildabooky_$stamp" . ($suffix ? "_$suffix" : '') . '.zip';
        $dbCopy = sys_get_temp_dir() . "/bildabooky_$stamp.sqlite";

        @unlink($dbCopy);
        DB::statement('VACUUM INTO ?', [$dbCopy]);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Sicherung kann nicht geschrieben werden: $zipPath");
        }
        $zip->addFile($dbCopy, 'bildabooky.sqlite');
        // Schlüssel für 2FA-Geheimnisse und Anmeldekarten — ohne ihn sind
        // diese nach einer Wiederherstellung auf einem neuen Server unlesbar
        // Key for 2FA secrets and login cards — without it they cannot
        // be read after a restore on a new server
        $key = self::keyPath();
        if ($withKey && is_file($key)) {
            $zip->addFile($key, '.app_key');
        }

        // Fotos/Zeichnungen/Ton und das Schullogo
        // Photos/drawings/sound and the school logo
        foreach (['medien' => config('bildabooky.media_path'), 'marke' => Branding::logoDirectory()] as $name => $folder) {
            $folder = rtrim($folder, '/');
            if (! is_dir($folder)) {
                continue;
            }
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if ($file->isFile()) {
                    $zip->addFile($file->getPathname(), $name . '/' . substr($file->getPathname(), strlen($folder) + 1));
                }
            }
        }
        $zip->close();
        // Enthält Kinderdaten und meist den App-Schlüssel: nicht für alle lesbar (Standard 04)
        // Holds children's data and usually the app key: not readable by everyone (standard 04)
        @chmod($zipPath, 0640);
        @unlink($dbCopy);
        return $zipPath;
    }

    /**
     * Ort des App-Schlüssels neben der Datenbank (im Container /data/.app_key)
     *
     * Location of the app key next to the database (in the container /data/.app_key)
     */
    public static function keyPath(): string
    {
        return dirname(config('database.connections.sqlite.database')) . '/.app_key';
    }

    public static function prune(string $dir, int $days): int
    {
        $n = 0;
        foreach (glob(rtrim($dir, '/') . '/bildabooky_*.zip') ?: [] as $f) {
            if (filemtime($f) < time() - $days * 86400) {
                @unlink($f) && $n++;
            }
        }
        return $n;
    }
}

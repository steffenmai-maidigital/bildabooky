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
    public static function create(string $targetDir, ?string $suffix = null): string
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
        $key = dirname(config('database.connections.sqlite.database')) . '/.app_key';
        if (is_file($key)) {
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
        @unlink($dbCopy);
        return $zipPath;
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

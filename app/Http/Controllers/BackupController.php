<?php

namespace App\Http\Controllers;

use App\Support\Backup;

class BackupController extends Controller
{
    /** Sofort-Sicherung zum Herunterladen (Datenbank + Medien) / Instant backup for download (database + media) */
    public function download()
    {
        $path = Backup::create(storage_path('app/backup'));
        return response()->download($path)->deleteFileAfterSend();
    }
}

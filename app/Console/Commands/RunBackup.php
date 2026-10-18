<?php

namespace App\Console\Commands;

use App\Support\Backup;
use Illuminate\Console\Command;

class RunBackup extends Command
{
    protected $signature = 'bildabooky:backup';

    /** Alter Name aus der Zeit vor Standard 10 / Old name from before standard 10 */
    protected $aliases = ['bildabooky:sicherung'];
    protected $description = 'Sichert Datenbank und Medien als ZIP in den Sicherungsordner';

    public function handle(): int
    {
        $dir = config('bildabooky.backup.path');
        $file = Backup::create($dir);
        $old = Backup::prune($dir, config('bildabooky.backup.days'));
        $this->info("Sicherung: $file" . ($old ? " ($old alte entfernt)" : ''));
        return self::SUCCESS;
    }
}

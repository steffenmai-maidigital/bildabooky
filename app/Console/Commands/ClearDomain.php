<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;

/**
 * Notausgang, falls im Admin-Bereich eine falsche Domain eingetragen wurde
 *
 * Emergency exit in case a wrong domain was entered in the admin area
 */
class ClearDomain extends Command
{
    protected $signature = 'bildabooky:clear-domain';

    /** Alter Name aus der Zeit vor Standard 10 / Old name from before standard 10 */
    protected $aliases = ['bildabooky:domain-leeren'];
    protected $description = 'Entfernt die eingetragene Domain (danach gilt wieder APP_URL)';

    public function handle(): int
    {
        Setting::forget('domain');
        $this->info('Domain entfernt — es gilt wieder APP_URL bzw. die aufgerufene Adresse.');
        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Notfall: neues Startpasswort für einen Admin (z. B. wenn es vergessen wurde)
 *   docker exec bildabooky php artisan bildabooky:admin-password [benutzername] [--disable-2fa]
 * (alter Name bildabooky:admin-passwort und Option --2fa-aus bleiben als Alias)
 *
 * Emergency: new initial password for an admin (e.g. if it was forgotten)
 *   docker exec bildabooky php artisan bildabooky:admin-password [username] [--disable-2fa]
 * (old name bildabooky:admin-passwort and option --2fa-aus remain as aliases)
 */
class AdminPassword extends Command
{
    protected $signature = 'bildabooky:admin-password {username=admin}
        {--disable-2fa : auch die Zwei-Faktor-Anmeldung zurücksetzen / also reset two-factor sign-in}
        {--2fa-aus : alter Name von --disable-2fa / old name of --disable-2fa}';

    /** Alter Name aus der Zeit vor Standard 10 / Old name from before standard 10 */
    protected $aliases = ['bildabooky:admin-passwort'];

    protected $description = 'Setzt ein neues Startpasswort für einen Admin (muss bei der Anmeldung geändert werden)';

    public function handle(): int
    {
        $user = User::where('username', strtolower($this->argument('username')))->first();
        if (! $user || ! $user->isAdmin()) {
            $this->error('Keinen Admin mit diesem Benutzernamen gefunden. Vorhandene Admins: '
                . (User::where('role', User::ADMIN)->pluck('username')->join(', ') ?: '—'));
            return self::FAILURE;
        }
        $new = User::strongPassword();
        $user->password = $new;
        $user->must_change_password = true;
        $user->save();
        \Illuminate\Support\Facades\RateLimiter::clear(\App\Http\Controllers\AuthController::lockKey($user->username));
        $disable2fa = $this->option('disable-2fa') || $this->option('2fa-aus');
        if ($disable2fa) {
            $user->disableTwoFactor();
        }
        $this->info("Neues Startpasswort für {$user->username}: $new");
        $this->line('Es muss bei der nächsten Anmeldung geändert werden.' . ($disable2fa ? ' Zwei-Faktor wurde zurückgesetzt.' : ''));
        return self::SUCCESS;
    }
}

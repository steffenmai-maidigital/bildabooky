<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Notzugang (Standard 09): neues Startpasswort für einen lokalen Admin, etwa wenn
 * es vergessen wurde oder das Schulverzeichnis ausfällt. Gibt es gar keinen
 * lokalen Admin (z. B. nur Admins aus dem Schulverzeichnis), legt der Befehl
 * einen an. Ein gesperrtes Konto ist danach wieder aktiv.
 *   docker exec bildabooky php artisan bildabooky:admin-password [benutzername] [--disable-2fa]
 * (alter Name bildabooky:admin-passwort und Option --2fa-aus bleiben als Alias)
 *
 * Emergency access (standard 09): new initial password for a local admin, e.g.
 * if it was forgotten or the school directory fails. If there is no local admin
 * at all (e.g. only admins from the school directory), the command creates
 * one. A locked account is active again afterwards.
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

    protected $description = 'Setzt ein neues Startpasswort für einen lokalen Admin oder legt einen an, wenn es keinen gibt';

    public function handle(): int
    {
        $name = strtolower(trim((string) $this->argument('username')));
        $user = User::where('username', $name)->first();
        $localAdmins = User::where('role', User::ADMIN)
            ->where(fn ($q) => $q->whereNull('auth_source')->orWhere('auth_source', '!=', User::DIRECTORY))
            ->orderBy('username')->pluck('username');
        $new = User::strongPassword();
        $disable2fa = $this->option('disable-2fa') || $this->option('2fa-aus');

        if ($user && $user->isAdmin() && $user->isLocal()) {
            // Vorhandenen lokalen Admin zurücksetzen / reset an existing local admin
            $wasLocked = ! $user->active;
            $user->password = $new;
            $user->must_change_password = true;
            $user->active = true;
            $user->save();
            \Illuminate\Support\Facades\RateLimiter::clear(\App\Http\Controllers\AuthController::lockKey($user->username));
            if ($disable2fa) {
                $user->disableTwoFactor();
            }
            $this->info("Neues Startpasswort für {$user->username}: $new");
            $this->line('Es muss bei der nächsten Anmeldung geändert werden. Andere Sitzungen dieses Kontos sind beendet.'
                . ($wasLocked ? ' Das Konto war gesperrt und ist wieder aktiv.' : '')
                . ($disable2fa ? ' Zwei-Faktor wurde zurückgesetzt.' : ''));

            return self::SUCCESS;
        }

        if ($localAdmins->isNotEmpty()) {
            $this->error("Keinen lokalen Admin „{$name}“ gefunden. Lokale Admins: " . $localAdmins->join(', '));

            return self::FAILURE;
        }

        // Kein lokaler Admin vorhanden: einen anlegen (Name frei, sonst abbrechen)
        // No local admin present: create one (name must be free, otherwise stop)
        if ($user || ! preg_match('/^[a-z0-9._-]{2,40}$/', $name)) {
            $this->error($user
                ? "Es gibt keinen lokalen Admin, und der Name „{$name}“ gehört schon einem anderen Konto"
                    . ($user->isLocal() ? '' : ' aus dem Schulverzeichnis') . '. Bitte einen freien Namen angeben, z. B.: php artisan bildabooky:admin-password notfall'
                : "Der Name „{$name}“ ist nicht erlaubt (2–40 Zeichen: Kleinbuchstaben, Ziffern, Punkt, Minus, Unterstrich).");

            return self::FAILURE;
        }
        User::create([
            'first_name'           => 'Admin', 'last_name' => '',
            'username'             => $name,
            'password'             => $new,
            'role'                 => User::ADMIN,
            'auth_source'          => User::LOCAL,
            'must_change_password' => true,
        ]);
        $this->info("Kein lokaler Admin vorhanden – neuer lokaler Admin angelegt: {$name}");
        $this->info("Startpasswort: $new");
        $this->line('Es muss bei der ersten Anmeldung geändert werden.'
            . ($disable2fa ? ' (--disable-2fa: Ein neues Konto hat noch keine Zwei-Faktor-Anmeldung.)' : ''));

        return self::SUCCESS;
    }
}

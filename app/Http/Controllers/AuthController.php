<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\ClassSessionLogin;
use App\Models\Setting;
use App\Models\User;
use App\Support\Mfa;
use App\Support\SessionRequirement;
use App\Support\Directory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Anmeldung mit Benutzername + Passwort, bei eingerichteter
 * Zwei-Faktor-Anmeldung zusätzlich mit Code aus der Authenticator-App.
 * Lehrkräfte und Admins können sich zusätzlich mit ihrem Schulkonto aus dem
 * Verzeichnis (Active Directory, Univention, OpenLDAP …) anmelden, wenn der
 * Admin das eingeschaltet hat (Standard 09). Kinder haben immer eigene Konten.
 *
 * Sign-in with username + password; with two-factor sign-in set up, also
 * with a code from the authenticator app.
 * Teachers and admins can also sign in with their school account from the
 * directory (Active Directory, Univention, OpenLDAP …) if the
 * admin has switched this on (Standard 09). Children always have own accounts.
 */
class AuthController extends Controller
{
    /**
     * Gruppen des Schulkontos aus der aktuellen Verzeichnis-Anmeldung
     *
     * Groups of the school account from the current directory sign-in
     */
    private array $groups = [];

    public function showLogin()
    {
        return view('auth.login', ['directory' => Directory::enabled()]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:60'],
            'password' => ['required', 'string', 'max:200'],
        ]);
        $data['username'] = Str::lower(trim($data['username']));

        // Kinder-Passwörter sind bewusst einfach — deshalb zweifach bremsen:
        //  - je Name und Gerät: 6 Versuche, dann 1 Minute Pause
        //  - je Name allein: 10 Fehlversuche, dann 30 Minuten gesperrt.
        //    Diese Sperre hängt NICHT an der IP-Adresse und lässt sich daher
        //    nicht mit gefälschten Absenderangaben umgehen.
        // Children's passwords are deliberately simple — hence a two-fold throttle:
        //  - per name and device: 6 attempts, then a 1-minute pause
        //  - per name alone: 10 failed attempts, then locked for 30 minutes.
        //    This lock does NOT depend on the IP address and therefore
        //    cannot be bypassed with forged sender details.
        $keyDevice = 'sign-in:' . $data['username'] . '|' . $request->ip();
        $keyName = self::lockKey($data['username']);
        foreach ([[$keyName, 10], [$keyDevice, 6]] as [$key, $max]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                $sec = RateLimiter::availableIn($key);
                return back()->withErrors(['username' => $sec > 120
                    ? __('Zu viele falsche Versuche. Dieses Konto ist für :n Minuten gesperrt — deine Lehrkraft kann das Passwort zurücksetzen.', ['n' => (int) ceil($sec / 60)])
                    : __('Zu viele Versuche. Bitte warte :n Sekunden.', ['n' => $sec])])
                    ->onlyInput('username');
            }
        }

        $fail = function (string $message) use ($keyDevice, $keyName) {
            RateLimiter::hit($keyDevice, 60);
            RateLimiter::hit($keyName, 1800);
            return back()->withErrors(['username' => $message])->onlyInput('username');
        };

        $user = User::where('username', $data['username'])->first();
        if ($user && $user->isLocal()) {
            // Eigenes Konto: Passwort prüfen
            // Own account: check password
            if (! Auth::validate($data)) {
                return $fail(__('Name oder Passwort stimmt nicht.'));
            }
        } else {
            // Schulkonto aus dem Verzeichnis (nur Lehrkräfte und Admins)
            // School account from the directory (teachers and admins only)
            $user = $this->fromDirectory($data['username'], $data['password'], $fail);
            if (! $user instanceof User) {
                return $user;   // Antwort mit Fehlermeldung / response with error message
            }
        }
        RateLimiter::clear($keyDevice);
        RateLimiter::clear($keyName);

        // Kinder ohne laufende Sitzung gar nicht erst weiterlassen (auch nicht zum zweiten Faktor)
        // Do not let children without a running session through at all (not even to the second factor)
        if (SessionRequirement::appliesTo($user) && ! SessionRequirement::runningFor($user)) {
            return $this->withoutSession();
        }

        // Zweiter Faktor nach Richtlinie (Einstellungen → 8); Kinder nie — bei ihnen ersetzt ihn die Klassen-Sitzung
        // Second factor according to policy (Settings → 8); never for children — the class session replaces it
        $policy = Mfa::policy($user, $this->groups);
        $request->session()->put('login.mfa', $policy);
        if ($policy !== Mfa::OFF && $user->hasTwoFactor()) {
            // Noch nicht anmelden — erst den zweiten Faktor abfragen
            // Do not sign in yet — ask for the second factor first
            $request->session()->put('login.2fa', ['id' => $user->id, 'until' => now()->addMinutes(5)->timestamp]);
            return redirect()->route('login.code');
        }

        return $this->proceed($request, $user);
    }

    /**
     * Anmeldung über das Schulverzeichnis: Konto beim ersten Mal anlegen,
     * Rolle aus den Gruppen (Admin-Gruppe → Admin, sonst Lehrkraft; mit
     * gesetzter Lehrkräfte-Gruppe nur deren Mitglieder). Liefert den
     * Benutzer oder eine Antwort mit Fehlermeldung.
     *
     * Sign-in via the school directory: create the account the first time,
     * role from the groups (admin group → admin, otherwise teacher; with a
     * teachers group set, only its members). Returns the
     * user or a response with an error message.
     */
    private function fromDirectory(string $username, string $password, callable $fail)
    {
        $directory = Directory::fromSettings();
        if (! $directory) {
            return $fail(__('Name oder Passwort stimmt nicht.'));
        }
        try {
            $entry = $directory->authenticate($username, $password);
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['username' => __('Das Schulverzeichnis ist gerade nicht erreichbar. Bitte später noch einmal versuchen oder den Admin fragen.')])->onlyInput('username');
        }
        if (! $entry) {
            return $fail(__('Name oder Passwort stimmt nicht.'));
        }

        $groups = $this->groups = $entry['groups'];
        $admin = Directory::isMemberOf($groups, Setting::get('ldap_group_admin'));
        $teacherGroup = Setting::get('ldap_group_teacher');
        if (! $admin && $teacherGroup && ! Directory::isMemberOf($groups, $teacherGroup)) {
            RateLimiter::hit(self::lockKey($username), 1800);
            return back()->withErrors(['username' => __('Dein Schulkonto ist für BilDaBooky nicht freigeschaltet. Frag den Admin.')])->onlyInput('username');
        }

        $name = preg_replace('/[^a-z0-9._-]/', '', Str::lower($entry['username'] ?: $username)) ?: Str::lower($username);
        $user = $this->findDirectoryUser($name, $entry['guid'] ?? null);
        if (! $user) {
            return $fail(__('Name oder Passwort stimmt nicht.'));
        }
        $parts = explode(' ', trim((string) $entry['name']), 2);
        $user->fill([
            'first_name'           => $entry['first_name'] ?: ($parts[0] ?: $name),
            'last_name'            => $entry['last_name'] ?: ($parts[1] ?? ''),
            'auth_source'          => User::DIRECTORY,
            'must_change_password' => false,
        ]);
        // Rolle kommt aus dem Verzeichnis; Kinder gibt es dort nie
        // Role comes from the directory; there are never children there
        $user->role = $admin ? User::ADMIN : User::TEACHER;
        if (! $user->exists) {
            // nie passend — die Anmeldung läuft über das Verzeichnis
            // never matches — sign-in goes via the directory
            $user->password = Str::random(40);
        }
        $user->save();

        return $user;
    }

    /**
     * Schulkonto zur Verzeichnis-Anmeldung finden oder neu vorbereiten (noch
     * nicht gespeichert). Zuerst über die unveränderliche Kennung (objectGUID /
     * entryUUID): So bleibt ein im Verzeichnis umbenanntes Konto dasselbe Konto
     * mit Rolle, Klassen, Büchern und Zwei-Faktor; der Name wird nachgezogen.
     * Sonst über den Namen (ältere Konten ohne Kennung), dann wird die Kennung
     * gespeichert. Eigene Konten haben nie eine Kennung und werden nie darüber
     * gefunden. null = abweisen (eigenes Konto mit gleichem Namen, Name schon
     * vergeben oder Name gehört einem anderen Verzeichniskonto).
     *
     * Find the school account for a directory sign-in or prepare a new one (not
     * saved yet). First by the immutable id (objectGUID / entryUUID): that way
     * an account renamed in the directory stays the same account with role,
     * classes, books and two-factor; the name is updated. Otherwise by name
     * (older accounts without an id), then the id is stored. Own (local)
     * accounts never have an id and are never found by it. null = refuse (own
     * account with the same name, name already taken or the name belongs to
     * another directory account).
     */
    private function findDirectoryUser(string $name, ?string $guid): ?User
    {
        $user = $guid ? User::where('guid', $guid)->where('auth_source', User::DIRECTORY)->first() : null;
        if ($user) {
            if ($user->username !== $name) {
                // Im Verzeichnis umbenannt: neuen Namen nur übernehmen, wenn ihn kein anderes Konto hat
                // Renamed in the directory: only adopt the new name if no other account has it
                if (User::where('username', $name)->whereKeyNot($user->id)->exists()) {
                    Log::warning('Verzeichnis-Anmeldung abgewiesen: umbenanntes Schulkonto, neuer Name schon vergeben / '
                        . 'directory sign-in refused: renamed school account, new name already taken',
                        ['user_id' => $user->id, 'old' => $user->username, 'new' => $name]);

                    return null;
                }
                Log::info('Schulkonto im Verzeichnis umbenannt / school account renamed in the directory',
                    ['user_id' => $user->id, 'old' => $user->username, 'new' => $name]);
                $user->username = $name;
            }

            return $user;
        }

        $user = User::firstOrNew(['username' => $name]);
        if ($user->exists && $user->isLocal()) {
            // eigenes Konto mit gleichem Namen hat Vorrang
            // an own account with the same name takes precedence
            return null;
        }
        if ($guid && $user->exists && $user->guid && $user->guid !== $guid) {
            // Der Name gehört einem anderen (z. B. gelöschten und neu angelegten) Verzeichniskonto:
            // nicht dessen Bücher und Klassen übernehmen — der Admin löscht das alte Konto
            // The name belongs to another (e.g. deleted and re-created) directory account:
            // do not take over its books and classes — the admin deletes the old account
            Log::warning('Verzeichnis-Anmeldung abgewiesen: Name gehört einem anderen Verzeichniskonto / '
                . 'directory sign-in refused: name belongs to another directory account',
                ['user_id' => $user->id, 'username' => $name]);

            return null;
        }
        if ($guid) {
            $user->guid = $guid;
        }

        return $user;
    }

    /**
     * Nach Passwort (und ggf. zweitem Faktor): Kinder brauchen eine Sitzung, evtl. mit Code
     *
     * After password (and second factor if any): children need a session, possibly with a code
     */
    private function proceed(Request $request, User $user)
    {
        if (! SessionRequirement::appliesTo($user)) {
            return $this->finish($request, $user);
        }
        $session = SessionRequirement::runningFor($user);
        if (! $session) {
            return $this->withoutSession();
        }
        if ($session->needsCode()) {
            $request->session()->put('login.session', ['id' => $user->id, 'session' => $session->id, 'until' => now()->addMinutes(5)->timestamp]);
            return redirect()->route('login.session-code');
        }
        return $this->finish($request, $user, $session);
    }

    private function withoutSession()
    {
        return redirect()->route('login')->with('no_session', true)->withInput(request()->only('username'));
    }

    public function showTwoFactor(Request $request)
    {
        if (! $this->pendingUser($request)) {
            return redirect()->route('login');
        }
        return view('auth.code');
    }

    public function twoFactor(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'max:20']]);
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login')->withErrors(['username' => __('Bitte melde dich erneut an.')]);
        }

        $key = '2fa:' . $user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $request->session()->forget('login.2fa');
            return redirect()->route('login')->withErrors(['username' => __('Zu viele falsche Codes. Bitte später erneut anmelden.')]);
        }

        if (! $user->verifyTwoFactor($request->input('code'))) {
            RateLimiter::hit($key, 300);
            return back()->withErrors(['code' => __('Der Code stimmt nicht.')]);
        }

        RateLimiter::clear($key);
        $request->session()->forget('login.2fa');
        return $this->proceed($request, $user);
    }

    /**
     * Sitzungscode (6 Ziffern) bei Sitzungen über mehrere Tage
     *
     * Session code (6 digits) for sessions spanning several days
     */
    public function showSessionCode(Request $request)
    {
        if (! $this->pendingSession($request)) {
            return redirect()->route('login');
        }
        return view('auth.session-code');
    }

    public function sessionCode(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'max:20']]);
        [$user, $session] = $this->pendingSession($request) ?? [null, null];
        if (! $user || ! $session || ! $session->isActive()) {
            $request->session()->forget('login.session');
            return redirect()->route('login')->withErrors(['username' => __('Bitte melde dich erneut an.')]);
        }

        // je Kind und Sitzung 5 Versuche, dann 5 Minuten Pause; je Sitzung höchstens 60 Fehlversuche je Stunde
        // per child and session 5 attempts, then a 5-minute pause; per session at most 60 failed attempts per hour
        $key = 'session-code:' . $session->id . ':' . $user->id;
        $keySession = 'session-code:' . $session->id;
        if (RateLimiter::tooManyAttempts($key, 5) || RateLimiter::tooManyAttempts($keySession, 60)) {
            return back()->withErrors(['code' => __('Zu viele falsche Codes. Bitte warte ein paar Minuten oder frag deine Lehrkraft.')]);
        }
        if (! $session->codeMatches($request->input('code'))) {
            RateLimiter::hit($key, 300);
            RateLimiter::hit($keySession, 3600);
            return back()->withErrors(['code' => __('Der Code stimmt nicht. Deine Lehrkraft kennt den richtigen Code.')]);
        }

        RateLimiter::clear($key);
        $request->session()->forget('login.session');
        return $this->finish($request, $user, $session);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    /**
     * Sperre je Benutzername (wird beim Zurücksetzen des Passworts aufgehoben)
     *
     * Lock per username (lifted when the password is reset)
     */
    public static function lockKey(string $username): string
    {
        return 'sign-in-name:' . Str::lower($username);
    }

    private function pendingUser(Request $request): ?User
    {
        $p = $request->session()->get('login.2fa');
        if (! $p || $p['until'] < time()) {
            return null;
        }
        return User::find($p['id']);
    }

    /** @return array{0: User, 1: ClassSession}|null */
    private function pendingSession(Request $request): ?array
    {
        $p = $request->session()->get('login.session');
        if (! $p || $p['until'] < time()) {
            return null;
        }
        $user = User::find($p['id']);
        $session = ClassSession::find($p['session']);
        return $user && $session ? [$user, $session] : null;
    }

    private function finish(Request $request, User $user, ?ClassSession $session = null)
    {
        // vor der Anmeldung gewählte Sprache übernehmen
        // adopt the language chosen before sign-in
        if (! $user->locale && $request->session()->has('locale')) {
            $user->locale = $request->session()->get('locale');
        }
        Auth::login($user);
        $request->session()->regenerate();
        // wirksame Zwei-Faktor-Stufe dieser Anmeldung (inkl. Verzeichnisgruppen) für Konto und Pflichtprüfung
        // effective two-factor level of this sign-in (incl. directory groups) for the account and the mandatory check
        $request->session()->put('mfa_policy', $request->session()->pull('login.mfa') ?: Mfa::policy($user));
        $user->forceFill(['last_login_at' => now()])->save();
        if ($session) {
            // für die Kontrolle bei jedem Aufruf und für das Protokoll der Lehrkräfte
            // for the check on every request and for the teachers' log
            $request->session()->put('class_session', $session->id);
            ClassSessionLogin::create(['class_session_id' => $session->id, 'user_id' => $user->id]);
        }
        return redirect()->intended(route('home'));
    }
}

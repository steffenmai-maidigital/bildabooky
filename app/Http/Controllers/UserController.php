<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Benutzerverwaltung.
 *  Admin:  alle Benutzer und Rollen
 *  Leiter: nur Ersteller in den eigenen Klassen
 *
 * Passwörter werden als Hash gespeichert. Zugangsdaten werden direkt nach
 * dem Anlegen bzw. Zurücksetzen einmal angezeigt. Nur wenn „Anmeldekarte“
 * angehakt ist, wird das Passwort zusätzlich verschlüsselt hinterlegt und
 * die Karte lässt sich später jederzeit neu drucken.
 *
 * User management.
 *  Admin:      all users and roles
 *  Teacher:    only pupils in their own classes
 *
 * Passwords are stored as a hash. Credentials are shown once directly
 * after creating or resetting. Only if “login card” is
 * ticked is the password additionally stored encrypted, and
 * the card can be reprinted at any time later.
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $actor = $request->user();
        $classes = SchoolClass::whereIn('id', $actor->managedClassIds())->orderBy('name')->get();

        $query = User::with('schoolClass', 'ledClasses')->withCount('books')
            ->orderBy('role')->orderBy('last_name')->orderBy('first_name');
        if (! $actor->isAdmin()) {
            $query->where('role', User::PUPIL)->whereIn('school_class_id', $actor->managedClassIds());
        }
        // alte Parameternamen (?klasse=, ?rolle=, ?suche=) vor Standard 10 gelten weiter
        // old parameter names (?klasse=, ?rolle=, ?suche=) from before standard 10 keep working
        if ($g = $request->integer('class') ?: $request->integer('klasse')) {
            $query->where(fn ($q) => $q->where('school_class_id', $g)
                ->orWhereHas('ledClasses', fn ($q) => $q->where('school_classes.id', $g)));
        }
        if ($r = $request->input('role') ?? $request->input('rolle')) {
            $r = [User::LEGACY_TEACHER => User::TEACHER, User::LEGACY_PUPIL => User::PUPIL][$r] ?? $r;
            $query->where('role', $r);
        }
        if ($s = trim((string) ($request->input('search') ?? $request->input('suche')))) {
            $query->where(fn ($q) => $q->where('first_name', 'like', "%$s%")
                ->orWhere('last_name', 'like', "%$s%")->orWhere('username', 'like', "%$s%"));
        }

        return view('manage.users', [
            'actor'       => $actor,
            'users'       => $query->get(),
            'classes'     => $classes,
            'credentials' => session('credentials'),
        ]);
    }

    public function store(Request $request)
    {
        $actor = $request->user();
        $data = $this->validated($request, $actor);

        $plain = $data['password'] ?: ($data['role'] === User::PUPIL ? User::easyPassword() : User::strongPassword());
        $user = User::create([
            'first_name'           => $data['first_name'],
            'last_name'            => $data['last_name'] ?? '',
            'username'             => $data['username'] ?: User::uniqueUsername($data['first_name'], $data['last_name'] ?? ''),
            'password'             => $plain,
            'role'                 => $data['role'],
            'school_class_id'      => $data['school_class_id'] ?? null,
            'must_change_password' => $data['role'] !== User::PUPIL,
            'mfa_policy'           => $data['mfa_policy'],
        ]);
        if ($request->boolean('card')) {
            $user->setCardPassword($plain);
            $user->save();
        }
        if ($actor->isAdmin() && ($user->isTeacher() || $user->isAdmin())) {
            $user->ledClasses()->sync($data['led_classes'] ?? []);
        }

        return redirect()->route('users.index')
            ->with('ok', __(':name wurde angelegt.', ['name' => $user->name]))
            ->with('credentials', [$this->access($user, $plain)]);
    }

    /**
     * Viele Ersteller auf einmal: eine Zeile je Person „Vorname Nachname“
     *
     * Many pupils at once: one line per person “First name Last name”
     */
    public function bulk(Request $request)
    {
        $actor = $request->user();
        $data = $request->validate([
            'school_class_id' => ['required', 'integer', Rule::in($actor->managedClassIds())],
            'names'           => ['required', 'string', 'max:10000'],
        ]);

        $credentials = [];
        foreach (preg_split('/\R/', $data['names']) as $line) {
            $line = trim(preg_replace('/\s+/', ' ', str_replace([',', ';', "\t"], ' ', $line)));
            if ($line === '' || mb_strlen($line) > 120) {
                continue;
            }
            $parts = explode(' ', $line);
            $first = array_shift($parts);
            $last = implode(' ', $parts);
            $plain = User::easyPassword();
            $user = User::create([
                'first_name'      => $first, 'last_name' => $last,
                'username'        => User::uniqueUsername($first, $last),
                'password'        => $plain, 'role' => User::PUPIL,
                'school_class_id' => $data['school_class_id'],
            ]);
            if ($request->boolean('card')) {
                $user->setCardPassword($plain);
                $user->save();
            }
            $credentials[] = $this->access($user, $plain);
            if (count($credentials) >= 60) {
                break;
            }
        }

        return redirect()->route('users.index', ['class' => $data['school_class_id']])
            ->with('ok', __(':n Schüler angelegt.', ['n' => count($credentials)]))
            ->with('credentials', $credentials);
    }

    public function edit(Request $request, User $user)
    {
        $actor = $request->user();
        abort_unless($actor->manages($user), 403);
        return view('manage.user-edit', [
            'actor'   => $actor,
            'user'    => $user->load('ledClasses'),
            'classes' => SchoolClass::whereIn('id', $actor->managedClassIds())->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $actor = $request->user();
        abort_unless($actor->manages($user), 403);
        $data = $this->validated($request, $actor, $user);
        if (! $user->isLocal()) {
            // Schulkonto: Rolle und Passwort kommen aus dem Verzeichnis
            // School account: role and password come from the directory
            $data['role'] = $user->role;
            $data['password'] = null;
        }

        if ($user->is($actor) && $data['role'] !== User::ADMIN) {
            return back()->withErrors(['role' => __('Du kannst dir die Admin-Rolle nicht selbst entziehen.')]);
        }
        if ($user->isAdmin() && $data['role'] !== User::ADMIN && User::where('role', User::ADMIN)->count() <= 1) {
            return back()->withErrors(['role' => __('Es muss mindestens einen Admin geben.')]);
        }

        $user->fill([
            'first_name'      => $data['first_name'],
            'last_name'       => $data['last_name'] ?? '',
            'username'        => $data['username'] ?: $user->username,
            'role'            => $data['role'],
            'school_class_id' => $data['school_class_id'] ?? null,
        ]);
        if ($actor->isAdmin()) {
            $user->mfa_policy = $data['mfa_policy'];
        }
        if ($data['password']) {
            \Illuminate\Support\Facades\RateLimiter::clear(AuthController::lockKey($user->username));
            $user->password = $data['password'];
            $user->must_change_password = ! $user->isPupil();
            $user->setCardPassword($request->boolean('card') ? $data['password'] : null);
        } elseif (! $request->boolean('card')) {
            $user->setCardPassword(null);   // Karte entfernen / remove card
        }
        $user->save();
        if ($actor->isAdmin()) {
            $classes = ($user->isTeacher() || $user->isAdmin()) ? ($data['led_classes'] ?? []) : [];
            $user->ledClasses()->sync($classes);
            // Fächer nur in Klassen unterrichten, in denen man (noch) unterrichtet
            // Teach subjects only in classes one (still) teaches in
            \Illuminate\Support\Facades\DB::table('class_subject_teacher')->where('user_id', $user->id)
                ->whereNotIn('school_class_id', $classes)->delete();
        }

        return redirect()->route('users.index')->with('ok', __('Änderungen gespeichert.'));
    }

    public function resetPassword(Request $request, User $user)
    {
        abort_unless($request->user()->manages($user) && ! $user->is($request->user()), 403);
        abort_unless($user->isLocal(), 403, 'Schulkonten haben hier kein Passwort.');
        $plain = $user->isPupil() ? User::easyPassword() : User::strongPassword();
        $user->password = $plain;
        $user->must_change_password = ! $user->isPupil();
        $user->setCardPassword($request->boolean('card') ? $plain : null);
        $user->save();
        \Illuminate\Support\Facades\RateLimiter::clear(AuthController::lockKey($user->username));

        return redirect()->route('users.index')
            ->with('ok', __('Neues Passwort für :name vergeben.', ['name' => $user->name]))
            ->with('credentials', [$this->access($user, $plain)]);
    }

    /**
     * Hinterlegte Anmeldekarten drucken: eine Person (?user=) oder eine ganze Klasse (?klasse=)
     *
     * Print stored login cards: one person (?user=) or a whole class (?klasse=)
     */
    public function cards(Request $request)
    {
        $actor = $request->user();
        $query = User::with('schoolClass')->whereNotNull('card_password')->orderBy('last_name')->orderBy('first_name');
        if ($id = $request->integer('user')) {
            $query->whereKey($id);
        } elseif ($g = $request->integer('class') ?: $request->integer('klasse')) {
            abort_unless(in_array($g, $actor->managedClassIds()), 403);
            $query->where('school_class_id', $g);
        } else {
            $query->whereIn('school_class_id', $actor->managedClassIds());
        }
        $cards = $query->get()->filter(fn ($u) => $actor->manages($u))
            ->map(fn ($u) => $this->access($u, $u->cardPassword() ?? '— bitte neu vergeben —'))->values()->all();

        return view('manage.cards', ['credentials' => $cards]);
    }

    public function resetTwoFactor(Request $request, User $user)
    {
        abort_unless($request->user()->manages($user), 403);
        $user->disableTwoFactor();
        return back()->with('ok', __('Zwei-Faktor-Anmeldung für :name zurückgesetzt.', ['name' => $user->name]));
    }

    public function destroy(Request $request, User $user)
    {
        $actor = $request->user();
        abort_unless($actor->manages($user) && ! $user->is($actor), 403);
        if ($user->isAdmin() && User::where('role', User::ADMIN)->count() <= 1) {
            return back()->withErrors(['user' => __('Der letzte Admin kann nicht gelöscht werden.')]);
        }
        $name = $user->name;
        $user->deleteWithBooks();
        return redirect()->route('users.index')->with('ok', __(':name und alle Bücher wurden gelöscht.', ['name' => $name]));
    }

    private function validated(Request $request, User $actor, ?User $user = null): array
    {
        // Admin ist ein Zusatz-Haken für Lehrkräfte; „admin“ als Rolle bleibt für ältere Formulare gültig
        // Admin is an extra tick box for teachers; “admin” as a role remains valid for older forms
        $roles = $actor->isAdmin() ? array_keys(User::ROLES) : [User::PUPIL];
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name'  => ['nullable', 'string', 'max:60'],
            'username'   => ['nullable', 'string', 'min:2', 'max:40', 'regex:/^[a-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($user?->id)],
            'password'        => ['nullable', 'string', 'min:4', 'max:200'],
            'role'            => ['required', Rule::in($roles)],
            'school_class_id' => ['nullable', 'integer', Rule::in($actor->managedClassIds())],
            'led_classes'     => ['nullable', 'array'],
            'led_classes.*'   => ['integer', Rule::exists('school_classes', 'id')],
            'mfa_policy'      => ['nullable', 'in:required,optional,off'],
        ], [
            'username.regex' => __('Der Benutzername darf nur Kleinbuchstaben, Ziffern, Punkt, Minus und Unterstrich enthalten.'),
        ]);
        if ($data['role'] === User::PUPIL && empty($data['school_class_id'])) {
            abort(back()->withErrors(['school_class_id' => __('Schüler brauchen eine Klasse.')])->withInput());
        }
        if ($data['role'] !== User::PUPIL && ! empty($data['password']) && mb_strlen($data['password']) < 8) {
            abort(back()->withErrors(['password' => __('Für Admins und Leiter mindestens 8 Zeichen.')])->withInput());
        }
        if ($actor->isAdmin() && in_array($data['role'], [User::TEACHER, User::ADMIN], true)) {
            $data['role'] = $request->boolean('admin') ? User::ADMIN : User::TEACHER;
        }
        $data['username'] = isset($data['username']) ? strtolower($data['username']) : null;
        $data['password'] ??= null;
        // Zwei-Faktor-Ausnahme nur für Lehrkräfte und Admins, nur durch Admins
        // Two-factor exception only for teachers and admins, only by admins
        $data['mfa_policy'] = ($actor->isAdmin() && $data['role'] !== User::PUPIL) ? ($data['mfa_policy'] ?? null) ?: null : null;
        return $data;
    }

    private function access(User $user, string $plain): array
    {
        return [
            'name'     => $user->name,
            'username' => $user->username,
            'password' => $plain,
            'class'    => $user->schoolClass?->name,
            'role'     => $user->roleLabel(),
        ];
    }
}

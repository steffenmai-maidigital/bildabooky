<?php

namespace App\Models;

use App\Models\Concerns\TwoFactor;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    // Zwei-Faktor-Anmeldung (gemeinsamer Baustein, Standard 09): hasTwoFactor(), verifyTwoFactor(), newRecoveryCodes(), disableTwoFactor()
    // Two-factor sign-in (shared building block, Standard 09): hasTwoFactor(), verifyTwoFactor(), newRecoveryCodes(), disableTwoFactor()
    use TwoFactor;

    public const ADMIN = 'admin';       // verwaltet die ganze Schule / manages the whole school
    public const TEACHER = 'teacher';   // Lehrkraft: leitet Klassen / teacher: leads classes
    public const PUPIL = 'pupil';       // Schülerin/Schüler: gestaltet eigene Bücher / pupil: designs their own books

    // Rollenwerte vor Standard 10 (nur zum Lesen alter Adressen und Einstellungen)
    // Role values before standard 10 (only for reading old addresses and settings)
    public const LEGACY_TEACHER = 'leiter';
    public const LEGACY_PUPIL = 'ersteller';

    public const LOCAL = 'local';       // eigenes Konto mit Passwort / own account with password
    // Schulkonto aus dem Verzeichnis (Lehrkräfte, Admins)
    // school account from the directory (teachers, admins)
    public const DIRECTORY = 'ldap';

    public const ROLES = [
        self::ADMIN   => 'Admin',
        self::TEACHER => 'Leiter (Lehrkraft)',
        self::PUPIL   => 'Ersteller (Schüler)',
    ];

    protected $fillable = ['first_name', 'last_name', 'username', 'password', 'role', 'school_class_id',
        'must_change_password', 'last_login_at', 'locale', 'fox', 'auth_source', 'mfa_policy'];
    protected $hidden = ['password', 'remember_token', 'card_password', 'two_factor_secret', 'two_factor_recovery_codes'];
    protected $casts = [
        'password'                  => 'hashed',
        'must_change_password'      => 'boolean',
        'last_login_at'             => 'datetime',
        'two_factor_secret'         => 'encrypted',
        'two_factor_recovery_codes' => 'encrypted:array',
        'two_factor_confirmed_at'   => 'datetime',
        'fox'                       => 'boolean',
    ];

    // ---- Beziehungen / Relations ----------------------------------------

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function ledClasses(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_leader')->orderBy('name');
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    // ---- Name und Rolle / Name and role ---------------------------------

    public function getNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Wie die Person angesprochen wird: Kinder mit Vornamen, Erwachsene mit vollem Namen
     *
     * How the person is addressed: children by first name, adults by full name
     */
    public function displayName(): string
    {
        return $this->isPupil() ? $this->first_name : $this->name;
    }

    /**
     * Eigenes Konto (Passwort in BilDaBooky) — sonst Schulkonto aus dem Verzeichnis
     *
     * Own (local) account (password in BilDaBooky) — otherwise school account from the directory
     */
    public function isLocal(): bool
    {
        return $this->auth_source !== self::DIRECTORY;
    }

    public function origin(): string
    {
        return $this->isLocal() ? __('eigenes Konto') : __('Schulkonto');
    }

    /**
     * Fuchs-Helfer anzeigen? Zentral abschaltbar (Admin), sonst je Person
     *
     * Show the fox helper? Can be switched off centrally (admin), otherwise per person
     */
    public function showsFox(): bool
    {
        return \App\Models\Setting::get('fox_enabled', '1') === '1' && $this->fox !== false;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ADMIN;
    }

    public function isTeacher(): bool
    {
        return $this->role === self::TEACHER;
    }

    public function isPupil(): bool
    {
        return $this->role === self::PUPIL;
    }

    /**
     * Admin oder Lehrkraft — darf Klassen betreuen und Schüler verwalten
     *
     * Admin or teacher — may look after classes and manage pupils
     */
    public function canLead(): bool
    {
        return $this->isAdmin() || $this->isTeacher();
    }

    public function roleLabel(): string
    {
        // Admins sind Lehrkräfte mit Verwaltungsrechten
        // Admins are teachers with administrative rights
        return $this->isAdmin()
            ? __('Lehrkraft') . ' · ' . __('Admin')
            : __(self::ROLES[$this->role] ?? $this->role);
    }

    // ---- Rechte / Permissions -------------------------------------------

    /**
     * Klassen, die diese Person verwaltet — Benutzer und Klassen (Admin: alle). Nicht für Bücher!
     *
     * Classes this person manages — users and classes (admin: all). Not for books!
     */
    public function managedClassIds(): array
    {
        return $this->managedIdsCache ??= match (true) {
            $this->isAdmin()   => SchoolClass::pluck('id')->all(),
            $this->isTeacher() => $this->ledClasses()->pluck('school_classes.id')->all(),
            default            => [],
        };
    }
    private ?array $managedIdsCache = null;

    /**
     * Fächer, die diese Lehrkraft in einer Klasse unterrichtet (IDs)
     *
     * Subjects this teacher teaches in a class (IDs)
     */
    public function subjectsIn(int $classId): array
    {
        return \Illuminate\Support\Facades\DB::table('class_subject_teacher')
            ->where('school_class_id', $classId)->where('user_id', $this->id)->pluck('subject_id')->map(fn ($v) => (int) $v)->all();
    }

    /**
     * Klassen, in denen diese Person unterrichtet — nur hierüber gibt es Zugriff auf
     * Bücher anderer. Gilt für Lehrkräfte UND Admins: Admin ist eine Verwaltungsrolle,
     * kein Zugang zu allen Büchern. Ein Admin, der selbst unterrichtet, wird wie eine
     * Lehrkraft den Klassen zugeordnet.
     *
     * Classes in which this person teaches — only through these is there access to
     * other people's books. Applies to teachers AND admins: admin is an administrative role,
     * not access to all books. An admin who teaches themselves is assigned to classes
     * like a teacher.
     */
    public function teachingClassIds(): array
    {
        return $this->teachingIdsCache ??= ($this->isAdmin() || $this->isTeacher())
            ? $this->ledClasses()->pluck('school_classes.id')->map(fn ($v) => (int) $v)->all()
            : [];
    }
    private ?array $teachingIdsCache = null;

    /**
     * Unterrichtet diese Person (Lehrkraft oder Admin) mindestens eine Klasse?
     *
     * Does this person (teacher or admin) teach at least one class?
     */
    public function teaches(): bool
    {
        return $this->teachingClassIds() !== [];
    }

    /** Klassen, in denen man Bücher lesen bzw. freigeben kann / Classes in which one can read or share books */
    public function libraryClassIds(): array
    {
        return $this->isPupil()
            ? array_filter([$this->school_class_id])
            : $this->teachingClassIds();
    }

    public function managesClass(SchoolClass $schoolClass): bool
    {
        return in_array($schoolClass->id, $this->managedClassIds());
    }

    /** Darf diese Person den anderen Benutzer bearbeiten? / May this person edit the other user? */
    public function manages(User $other): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        return $this->isTeacher()
            && $other->isPupil()
            && $other->school_class_id
            && in_array($other->school_class_id, $this->managedClassIds());
    }

    // ---- Zwei-Faktor-Anmeldung / Two-factor sign-in -----------------------

    /**
     * Pflicht laut Richtlinie (Einstellungen → Anmeldung); bei der Anmeldung mit den Verzeichnisgruppen ermittelt
     *
     * Mandatory according to policy (Settings → Sign-in); determined at sign-in with the directory groups
     */
    public function twoFactorRequired(): bool
    {
        return $this->mfaPolicy() === \App\Support\Mfa::REQUIRED;
    }

    /**
     * Zweiter Faktor überhaupt möglich? (Kinder nie — bei ihnen ersetzt ihn die Klassen-Sitzung)
     *
     * Is a second factor possible at all? (Never for children — the class session replaces it)
     */
    public function twoFactorAllowed(): bool
    {
        return $this->mfaPolicy() !== \App\Support\Mfa::OFF;
    }

    /**
     * Wirksame Stufe: aus der Sitzung (inkl. Verzeichnisgruppen), sonst frisch berechnet
     *
     * Effective level: from the session (incl. directory groups), otherwise freshly calculated
     */
    public function mfaPolicy(): string
    {
        $s = session('mfa_policy');
        if ($s && auth()->id() === $this->id && isset(\App\Support\Mfa::LEVELS[$s])) {
            return $s;
        }
        return \App\Support\Mfa::policy($this);
    }

    // ---- Anmeldekarte (optional) / Login card (optional) ------------------

    public function hasCard(): bool
    {
        return (bool) $this->card_password;
    }

    /**
     * Passwort verschlüsselt hinterlegen (null = Karte entfernen)
     *
     * Store the password encrypted (null = remove card)
     */
    public function setCardPassword(?string $plain): void
    {
        $this->card_password = $plain === null ? null : Crypt::encryptString($plain);
    }

    public function cardPassword(): ?string
    {
        if (! $this->card_password) {
            return null;
        }
        try {
            return Crypt::decryptString($this->card_password);
        } catch (DecryptException) {
            return null;   // APP_KEY wurde gewechselt — Passwort neu vergeben / APP_KEY changed — set a new password
        }
    }

    // ---- Anlegen / Creation -----------------------------------------------

    /**
     * Leicht tipp- und merkbare Start-Passwörter für Kinder: „tiger47“
     *
     * Initial passwords for children that are easy to type and remember: “tiger47”
     */
    public static function easyPassword(): string
    {
        // rund 100 leichte Wörter × 90 Zahlen ≈ 9.000 Möglichkeiten — zusammen mit
        // der Sperre nach 10 Fehlversuchen je Konto praktisch nicht zu erraten
        // around 100 easy words × 90 numbers ≈ 9,000 possibilities — together with
        // the lock after 10 failed attempts per account practically impossible to guess
        $words = ['apfel', 'birne', 'blume', 'dino', 'drache', 'eule', 'fuchs', 'hase', 'igel',
            'kiwi', 'koala', 'krone', 'lama', 'loewe', 'mond', 'maus', 'nuss', 'panda', 'pilz',
            'pinguin', 'rabe', 'rakete', 'robbe', 'sonne', 'stern', 'tiger', 'wal', 'wolke',
            'zebra', 'biber', 'delfin', 'katze', 'hummel', 'kakao', 'ritter', 'schiff', 'baum',
            'anker', 'banane', 'blitz', 'boot', 'brezel', 'burg', 'dachs', 'eimer', 'elch', 'ente',
            'erdbeere', 'feder', 'fisch', 'flamingo', 'frosch', 'gans', 'giraffe', 'gurke', 'hafen',
            'hamster', 'hirsch', 'honig', 'insel', 'kaktus', 'kamel', 'karotte', 'kerze', 'kirsche',
            'koffer', 'komet', 'kran', 'krokodil', 'leiter', 'lupe', 'marmor', 'melone', 'muschel',
            'nebel', 'otter', 'papagei', 'pirat', 'planet', 'pony', 'quelle', 'regen', 'riese',
            'roller', 'sattel', 'schnecke', 'segel', 'spatz', 'teddy', 'tomate', 'trommel', 'tulpe',
            'turm', 'uhu', 'vulkan', 'wiesel', 'zauber', 'zitrone', 'zwerg'];
        return $words[random_int(0, count($words) - 1)] . random_int(10, 99);
    }

    /**
     * Startpasswort für Admins/Leiter (muss bei der ersten Anmeldung geändert werden)
     *
     * Initial password for admins/teachers (must be changed at first sign-in)
     */
    public static function strongPassword(): string
    {
        return Str::password(12, symbols: false);
    }

    /**
     * Benutzername aus dem Vornamen: „Jörg Müller“ → „joerg“, bei Bedarf „joergm“, „joerg2“
     *
     * Username from the first name: “Jörg Müller” → “joerg”, if needed “joergm”, “joerg2”
     */
    public static function uniqueUsername(string $first, string $last = ''): string
    {
        $clean = function (string $s) {
            $s = strtr(mb_strtolower(trim($s)), ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
            return preg_replace('/[^a-z0-9]/', '', Str::ascii($s));
        };
        $base = $clean(Str::before($first . ' ', ' ')) ?: 'user';
        $candidates = [$base];
        if ($l = $clean($last)) {
            $candidates[] = $base . $l[0];
            $candidates[] = $base . '.' . $l;
        }
        foreach ($candidates as $c) {
            if (! static::where('username', $c)->exists()) {
                return $c;
            }
        }
        for ($i = 2; static::where('username', $base . $i)->exists(); $i++);
        return $base . $i;
    }

    /** Löscht die Person samt Büchern und Mediendateien / Deletes the person including books and media files */
    public function deleteWithBooks(): void
    {
        $this->books()->get()->each->deleteWithMedia();
        $this->delete();
    }
}

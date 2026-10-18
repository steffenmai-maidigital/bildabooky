<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Anmelde-Sitzung einer Klasse: Nur solange sie läuft, können sich die Kinder
 * der Klasse anmelden — mit Passwort UND dem 6-stelligen Sitzungscode (er
 * ersetzt bei Kindern den zweiten Faktor). Gestartet, beendet und angemeldet wird dokumentiert
 * (60 Tage, danach aufgeräumt).
 *
 * Sign-in session of a class: only while it is running can the children
 * of the class sign in — with password AND the 6-digit session code (for
 * children it replaces the second factor). Starting, ending and signing in are documented
 * (60 days, cleaned up afterwards).
 */
class ClassSession extends Model
{
    protected $table = 'class_sessions';
    protected $fillable = ['school_class_id', 'started_by', 'reason', 'starts_at', 'ends_at', 'code', 'closed_at', 'closed_by', 'close_reason'];
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'closed_at' => 'datetime'];
    protected $hidden = ['code'];

    public const MINUTES = [60, 120, 180];
    public const MAX_DAYS = 7;
    public const END_TIME = '17:00';   // Sitzungen über Tage enden um 17 Uhr / sessions spanning days end at 5 pm
    public const KEEP_DAYS = 60;

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function logins(): HasMany
    {
        return $this->hasMany(ClassSessionLogin::class, 'class_session_id');
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(ClassSessionExtension::class, 'class_session_id')->orderBy('created_at');
    }

    public const EXTEND_MINUTES = [30, 60, 120];

    /** Spätestes mögliches Ende: in 7 Tagen um 17 Uhr / Latest possible end: in 7 days at 5 pm */
    public static function latestEnd(): Carbon
    {
        return self::endOnDay(today()->addDays(self::MAX_DAYS)->toDateString());
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereNull('closed_at')->where('starts_at', '<=', now())->where('ends_at', '>', now());
    }

    public static function activeFor(?int $classId): ?self
    {
        return $classId ? static::active()->where('school_class_id', $classId)->latest('starts_at')->first() : null;
    }

    public function isActive(): bool
    {
        return ! $this->closed_at && $this->starts_at->lte(now()) && $this->ends_at->gt(now());
    }

    public function needsCode(): bool
    {
        return $this->code !== null;
    }

    /**
     * Klartext-Code (6 Ziffern) für die Anzeige bei den Lehrkräften
     *
     * Plain-text code (6 digits) for display to the teachers
     */
    public function plainCode(): ?string
    {
        if (! $this->code) {
            return null;
        }
        try {
            return Crypt::decryptString($this->code);
        } catch (Throwable) {
            return null;
        }
    }

    public function codeMatches(string $input): bool
    {
        $expected = $this->plainCode();
        return $expected !== null && hash_equals($expected, preg_replace('/\D/', '', $input));
    }

    public static function newCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /** Status für die Anzeige: läuft / beendet / abgelaufen / Status for display: running / ended / expired */
    public function status(): string
    {
        return $this->closed_at ? 'ended' : ($this->ends_at->gt(now()) ? 'running' : 'expired');
    }

    /**
     * Endzeit einer Sitzung über Tage: der gewählte Tag um 17 Uhr
     *
     * End time of a session spanning days: the chosen day at 5 pm
     */
    public static function endOnDay(string $date): Carbon
    {
        return Carbon::parse($date . ' ' . self::END_TIME);
    }

    /**
     * Alles älter als 60 Tage löschen (samt Anmeldungen)
     *
     * Delete everything older than 60 days (including sign-ins)
     */
    public static function cleanUp(): int
    {
        return static::where('ends_at', '<', now()->subDays(self::KEEP_DAYS))->delete();
    }
}

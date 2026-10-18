<?php

namespace App\Support;

use App\Models\ClassSession;
use App\Models\Setting;
use App\Models\User;

/**
 * Sitzungspflicht für Kinder (vom Admin abschaltbar)
 *
 * Mandatory session for children (can be switched off by the admin)
 */
class SessionRequirement
{
    public static function required(): bool
    {
        return Setting::get('session_required', '1') === '1';
    }

    /**
     * Gilt die Sitzungspflicht für diese Person? (nur Kinder mit Klasse)
     *
     * Does the mandatory session apply to this person? (only children with a class)
     */
    public static function appliesTo(User $user): bool
    {
        return self::required() && $user->isPupil();
    }

    /** Laufende Sitzung der Klasse eines Kindes / Running session of a child's class */
    public static function runningFor(User $user): ?ClassSession
    {
        return ClassSession::activeFor($user->school_class_id);
    }
}

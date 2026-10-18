<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Einfache Schlüssel/Wert-Einstellungen, je Anfrage zwischengespeichert
 *
 * Simple key/value settings, cached per request
 */
class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    private static ?array $cache = null;

    public static function all_(): array
    {
        if (self::$cache === null) {
            try {
                self::$cache = Schema::hasTable('settings')
                    ? static::query()->pluck('value', 'key')->all()
                    : [];
            } catch (Throwable) {
                self::$cache = [];   // z. B. während der ersten Migration / e.g. during the first migration
            }
        }
        return self::$cache;
    }

    public static function get(string $key, $default = null)
    {
        $v = self::all_()[$key] ?? null;
        return ($v === null || $v === '') ? $default : $v;
    }

    public static function put(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        self::$cache = null;
    }

    /**
     * Alias für die gemeinsamen Bausteine (Verzeichnis, Fuchs) — wie in crewgate und linkhub
     *
     * Alias for the shared building blocks (directory, fox) — as in crewgate and linkhub
     */
    public static function set(string $key, $value): void
    {
        static::put($key, $value);
    }

    public static function forget(string $key): void
    {
        static::whereKey($key)->delete();
        self::$cache = null;
    }
}

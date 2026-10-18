<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Schulfach (z. B. Deutsch, Mathe) — wird Klassen und Lehrkräften zugeordnet
 *
 * School subject (e.g. German, maths) — assigned to classes and teachers
 */
class Subject extends Model
{
    protected $fillable = ['name', 'symbol', 'position'];

    /**
     * Auswahl an Symbolen für Fächer (Twemoji, siehe public/img/emoji)
     *
     * Selection of symbols for subjects (Twemoji, see public/img/emoji)
     */
    public const SYMBOLS = ['📖', '🔢', '🌱', '🎨', '🎵', '💬', '⚽', '🔬', '🌍', '🕊️', '💻', '✏️', '📚', '🧩', '🎭', '🍎'];

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_subject');
    }

    public function scopeSorted(Builder $q): Builder
    {
        return $q->orderBy('position')->orderBy('name');
    }

    public function displayName(): string
    {
        return trim($this->symbol . ' ' . $this->name);
    }
}

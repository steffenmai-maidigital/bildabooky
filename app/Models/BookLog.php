<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Änderungsprotokoll eines Buchs. Der Editor speichert sehr oft automatisch;
 * aufeinanderfolgende Änderungen derselben Person an derselben Seite werden
 * deshalb innerhalb von 10 Minuten zu EINEM Eintrag zusammengefasst.
 *
 * Change log of a book. The editor saves automatically very often;
 * consecutive changes by the same person to the same page are
 * therefore merged into ONE entry within 10 minutes.
 */
class BookLog extends Model
{
    public const MERGE_MINUTES = 10;

    protected $fillable = ['book_id', 'user_id', 'page_id', 'page', 'action', 'details'];
    protected $casts = ['details' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Eintrag schreiben; $merge fasst gleiche Aktionen derselben Person zusammen
     *
     * Write an entry; $merge merges identical actions by the same person
     */
    public static function record(Book $book, ?User $user, string $action, array $details = [], ?Page $page = null, ?callable $merge = null): void
    {
        $pageNumber = $page ? $book->pages()->pluck('id')->search($page->id) : false;
        $pageNumber = $pageNumber === false ? null : $pageNumber + 1;

        if ($merge) {
            $last = static::where('book_id', $book->id)->latest('id')->first();
            if ($last && $last->action === $action
                && $last->user_id === $user?->id
                && $last->page_id === $page?->id
                && $last->updated_at->gt(now()->subMinutes(self::MERGE_MINUTES))) {
                $last->details = $merge($last->details ?? [], $details);
                $last->page = $pageNumber ?? $last->page;
                $last->updated_at = now();
                $last->save();
                return;
            }
        }

        static::create([
            'book_id' => $book->id, 'user_id' => $user?->id, 'page_id' => $page?->id,
            'page' => $pageNumber, 'action' => $action, 'details' => $details,
        ]);
    }

    /**
     * Unterschiede zweier Elementlisten einer Seite: was kam dazu, was fiel weg, was änderte sich
     *
     * Differences between two element lists of a page: what was added, what was removed, what changed
     */
    public static function diff(array $old, array $new): array
    {
        $a = collect($old)->keyBy('id');
        $n = collect($new)->keyBy('id');
        $d = ['added' => [], 'removed' => [], 'content' => 0, 'layout' => 0, 'texts' => []];

        foreach ($n as $id => $e) {
            if (! $a->has($id)) {
                $d['added'][$e['type']] = ($d['added'][$e['type']] ?? 0) + 1;
                if ($e['type'] === 'text' && trim($e['text'] ?? '') !== '') {
                    $d['texts'][] = mb_substr(trim($e['text']), 0, 80);
                }
                continue;
            }
            $o = $a[$id];
            $layout = ['x', 'y', 'w', 'h', 'r'];
            $contentOld = array_diff_key($o, array_flip($layout));
            $contentNew = array_diff_key($e, array_flip($layout));
            if ($contentOld != $contentNew) {
                $d['content']++;
                if ($e['type'] === 'text' && ($o['text'] ?? '') !== ($e['text'] ?? '') && trim($e['text'] ?? '') !== '') {
                    $d['texts'][] = mb_substr(trim($e['text']), 0, 80);
                }
            } elseif (array_intersect_key($o, array_flip($layout)) != array_intersect_key($e, array_flip($layout))) {
                $d['layout']++;
            }
        }
        foreach ($a as $id => $e) {
            if (! $n->has($id)) {
                $d['removed'][$e['type']] = ($d['removed'][$e['type']] ?? 0) + 1;
            }
        }
        return $d;
    }

    public static function diffIsEmpty(array $d): bool
    {
        return ! $d['added'] && ! $d['removed'] && ! $d['content'] && ! $d['layout'] && ! array_key_exists('background', $d);
    }

    /** Zwei Seiten-Diffs zusammenzählen (für das Bündeln) / Add up two page diffs (for merging) */
    public static function diffSum(array $old, array $new): array
    {
        foreach (['added', 'removed'] as $k) {
            foreach ($new[$k] ?? [] as $type => $n) {
                $old[$k][$type] = ($old[$k][$type] ?? 0) + $n;
            }
        }
        $old['content'] = ($old['content'] ?? 0) + ($new['content'] ?? 0);
        $old['layout'] = ($old['layout'] ?? 0) + ($new['layout'] ?? 0);
        // nur die jeweils letzte Fassung eines Textes behalten (Tippen speichert oft)
        // keep only the latest version of each text (typing saves often)
        $old['texts'] = array_slice(array_values(array_unique(array_merge($old['texts'] ?? [], $new['texts'] ?? []))), -3);
        if (array_key_exists('background', $new)) {
            $old['background'] = $new['background'];
        }
        return $old;
    }

    /** Lesbare Beschreibung in der aktuellen Sprache / Readable description in the current language */
    public function description(): string
    {
        $d = $this->details ?? [];
        $types = ['text' => __('Text'), 'image' => __('Bild'), 'audio' => __('Ton'), 'sticker' => __('Sticker'), 'shape' => __('Form')];
        $list = fn (array $z) => collect($z)->map(fn ($n, $t) => ($n > 1 ? $n . '× ' : '') . ($types[$t] ?? $t))->join(', ');

        return match ($this->action) {
            'created' => __('Buch angelegt'),
            'version' => __('Eigene Version aus der Vorlage „:titel“ angelegt', ['titel' => $d['template'] ?? '']),
            'copy'    => __('Kopie von „:titel“ angelegt', ['titel' => $d['from'] ?? '']),
            'title'   => __('Titel geändert: „:alt“ → „:neu“', ['alt' => $d['old'] ?? '', 'neu' => $d['new'] ?? '']),
            'format'  => __('Format geändert'),
            'sharing' => match ($d['new'] ?? 'private') {
                'class'   => __('Mit der Klasse geteilt'),
                'teacher' => __('Mit der Lehrkraft geteilt'),
                default   => __('Teilen aufgehoben (nur ich)'),
            },
            'class'               => __('Klasse geändert'),
            'subject'             => empty($d['new']) ? __('Fach entfernt (ohne Fach)') : __('In das Fach „:fach“ verschoben', ['fach' => $d['new']]),
            'page_added'          => __('Seite hinzugefügt'),
            'page_removed'        => __('Seite gelöscht'),
            'order'               => __('Seiten umsortiert'),
            'submitted'           => __('Als fertig abgegeben'),
            'submission_returned' => __('Abgabe zurückgenommen'),
            'returned'            => empty($d['note']) ? __('Ans Kind zurückgegeben') : __('Ans Kind zurückgegeben: „:notiz“', ['notiz' => $d['note']]),
            'page'                => collect([
                ! empty($d['added']) ? __('hinzugefügt: :was', ['was' => $list($d['added'])]) : null,
                ! empty($d['removed']) ? __('gelöscht: :was', ['was' => $list($d['removed'])]) : null,
                ! empty($d['content']) ? __('geändert: :n', ['n' => $d['content']]) : null,
                ! empty($d['layout']) ? __('verschoben/gedreht: :n', ['n' => $d['layout']]) : null,
                array_key_exists('background', $d) ? __('Seitenfarbe geändert') : null,
            ])->filter()->join(' · ') ?: __('Seite bearbeitet'),
            default               => $this->action,
        };
    }
}

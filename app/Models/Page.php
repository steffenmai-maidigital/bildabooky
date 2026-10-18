<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Page extends Model
{
    protected $fillable = ['book_id', 'position', 'background', 'elements', 'version'];

    public const FONTS = ['sans', 'rounded', 'serif', 'hand', 'heavy'];
    public const SHAPES = ['rectangle', 'rounded', 'circle'];

    /**
     * Schrift- und Formwerte vor Standard 10 (z. B. aus einem noch offenen Editor) → neue Werte
     *
     * Font and shape values from before standard 10 (e.g. from an editor still open) → new values
     */
    public const LEGACY_VALUES = ['rund' => 'rounded', 'dick' => 'heavy', 'rechteck' => 'rectangle', 'kreis' => 'circle'];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function elementList(): array
    {
        $list = json_decode($this->elements ?? '[]', true);
        return is_array($list) ? $list : [];
    }

    public function toEditorArray(): array
    {
        return [
            'id'         => $this->id,
            'version'    => (int) $this->version,
            'background' => $this->background,
            'elements'   => $this->elementList(),
        ];
    }

    public static function color($v, string $fallback = ''): string
    {
        return is_string($v) && preg_match('/^#[0-9a-fA-F]{3,8}$/', $v) ? $v : $fallback;
    }

    private static function num($v, float $min, float $max, float $fallback): float
    {
        if (! is_numeric($v)) {
            return $fallback;
        }
        return round(max($min, min($max, (float) $v)), 2);
    }

    /**
     * Ein Element aus dem Editor prüfen und auf erlaubte Felder kürzen.
     * Die Darstellung setzt Text nur per textContent und Farben nur als
     * Hex-Werte — es gelangt kein HTML aus der Datenbank in die Seite.
     *
     * Check an element from the editor and trim it to allowed fields.
     * Rendering sets text only via textContent and colours only as
     * hex values — no HTML from the database reaches the page.
     */
    public static function sanitizeElement($e): ?array
    {
        if (! is_array($e) || ! isset($e['type'])) {
            return null;
        }
        $id = is_string($e['id'] ?? null) && preg_match('/^[A-Za-z0-9_-]{1,40}$/', $e['id'])
            ? $e['id'] : 'e' . bin2hex(random_bytes(5));

        $out = [
            'id'   => $id,
            'type' => $e['type'],
            'x'    => self::num($e['x'] ?? 0, -3000, 4000, 0),
            'y'    => self::num($e['y'] ?? 0, -3000, 4000, 0),
            'w'    => self::num($e['w'] ?? 100, 8, 5000, 100),
            'h'    => self::num($e['h'] ?? 100, 8, 5000, 100),
            'r'    => self::num($e['r'] ?? 0, -360, 360, 0),
        ];

        foreach (['font', 'shape'] as $k) {
            if (is_string($e[$k] ?? null) && isset(self::LEGACY_VALUES[$e[$k]])) {
                $e[$k] = self::LEGACY_VALUES[$e[$k]];
            }
        }

        switch ($e['type']) {
            case 'text':
                $out += [
                    'text'   => mb_substr(is_string($e['text'] ?? null) ? $e['text'] : '', 0, 5000),
                    'size'   => self::num($e['size'] ?? 48, 8, 400, 48),
                    'color'  => self::color($e['color'] ?? null, '#222222'),
                    'bg'     => self::color($e['bg'] ?? null, ''),
                    'font'   => in_array($e['font'] ?? null, self::FONTS, true) ? $e['font'] : 'rounded',
                    'bold'   => (bool) ($e['bold'] ?? false),
                    'italic' => (bool) ($e['italic'] ?? false),
                    'align'  => in_array($e['align'] ?? null, ['left', 'center', 'right'], true) ? $e['align'] : 'left',
                ];
                break;
            case 'image':
            case 'audio':
                if (! is_numeric($e['media'] ?? null)) {
                    return null;
                }
                $out['media'] = (int) $e['media'];
                if ($e['type'] === 'audio') {
                    $out['color'] = self::color($e['color'] ?? null, '#ff7a3d');
                } else {
                    $out['flip'] = (bool) ($e['flip'] ?? false);
                }
                break;
            case 'sticker':
                $char = is_string($e['char'] ?? null) ? mb_substr($e['char'], 0, 12) : '';
                if ($char === '' || preg_match('/[<>&"]/', $char)) {
                    return null;
                }
                $out['char'] = $char;
                break;
            case 'shape':
                $out['shape'] = in_array($e['shape'] ?? null, self::SHAPES, true) ? $e['shape'] : 'rectangle';
                $out['fill'] = self::color($e['fill'] ?? null, '#ffd166');
                break;
            default:
                return null;
        }
        return $out;
    }
}

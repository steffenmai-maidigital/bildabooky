<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Entfernte Fotos, Zeichnungen und Töne endgültig löschen (nächtlich, siehe
 * routes/console.php). Ein Medium, das auf keiner Seite irgendeines Buchs mehr
 * steht (auch keiner Version, Vorlage oder Kopie), bekommt beim ersten Lauf das
 * Datum `unused_since`. Ist es danach `--days` Tage (Standard 30) unbenutzt,
 * verschwinden Datei und Datensatz. Steht es vorher wieder auf einer Seite,
 * wird das Datum gelöscht. Die Frist beginnt also frühestens beim Hochladen
 * bzw. beim letzten Bezug.
 *   docker exec bildabooky php artisan bildabooky:prune-media [--days=30] [--dry-run]
 *
 * Delete removed photos, drawings and sounds for good (nightly, see
 * routes/console.php). A medium that is no longer on any page of any book
 * (no version, template or copy either) gets the date `unused_since` on the
 * first run. If it then stays unused for `--days` days (default 30), file and
 * record are removed. If it is placed on a page again before that, the date
 * is cleared. So the grace period starts at the upload or the last use at the
 * earliest.
 */
class PruneMedia extends Command
{
    protected $signature = 'bildabooky:prune-media
        {--days=30 : Tage ohne Bezug bis zum Löschen / days without use until deletion}
        {--dry-run : nur zählen, nichts ändern / only count, change nothing}';

    protected $description = 'Löscht Fotos, Zeichnungen und Töne, die seit 30 Tagen auf keiner Seite mehr stehen';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $dry = (bool) $this->option('dry-run');
        $limit = now()->subDays($days);

        // Alle Medien, die auf irgendeiner Seite stehen – über alle Bücher hinweg
        // All media placed on any page – across all books
        $used = [];
        Page::query()->select('id', 'elements')->chunkById(500, function ($pages) use (&$used) {
            foreach ($pages as $page) {
                $used += $page->mediaIds();
            }
        });

        $count = ['marked' => 0, 'back' => 0, 'deleted' => 0, 'waiting' => 0];
        $disk = Storage::disk('media');
        Media::query()->chunkById(500, function ($list) use ($used, $limit, $dry, $disk, &$count) {
            foreach ($list as $media) {
                if (isset($used[$media->id])) {
                    // wieder (oder noch) in Gebrauch: Frist aufheben
                    // in use again (or still): cancel the grace period
                    if ($media->unused_since) {
                        $count['back']++;
                        $dry || $media->forceFill(['unused_since' => null])->save();
                    }
                    continue;
                }
                if (! $media->unused_since) {
                    $count['marked']++;
                    $dry || $media->forceFill(['unused_since' => now()])->save();
                    continue;
                }
                if ($media->unused_since->gt($limit) || $this->stillPlaced($media->id)) {
                    $count['waiting']++;
                    continue;
                }
                $count['deleted']++;
                if (! $dry) {
                    $disk->delete($media->path);
                    $media->delete();
                }
            }
        });

        $dry || Log::info('Entfernte Medien aufgeräumt / removed media pruned', $count + ['days' => $days]);
        $this->info(sprintf('Medien ohne Bezug: %d gelöscht, %d warten (davon %d neu), %d wieder in Gebrauch – Frist %d Tage%s',
            $count['deleted'], $count['waiting'] + $count['marked'], $count['marked'], $count['back'], $days, $dry ? ' (Probelauf)' : ''));

        return self::SUCCESS;
    }

    /**
     * Letzte Prüfung direkt vor dem Löschen: Wurde das Medium seit dem Einlesen der
     * Seiten wieder eingesetzt? Lieber einmal zu viel behalten.
     *
     * Last check right before deleting: has the medium been placed again since the
     * pages were read? Better to keep it once too often.
     */
    private function stillPlaced(int $id): bool
    {
        return Page::where('elements', 'like', '%"media":' . $id . '%')->get(['id', 'elements'])
            ->contains(fn (Page $page) => isset($page->mediaIds()[$id]));
    }
}

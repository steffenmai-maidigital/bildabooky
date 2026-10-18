<?php

namespace App\Console\Commands;

use App\Support\SummerHoliday;
use Illuminate\Console\Command;

/**
 * Sommerferienmodus ausführen, wenn der Tag erreicht ist (läuft täglich per Zeitplan)
 *
 * Run summer holiday mode once the day has come (runs daily via the scheduler)
 */
class SummerHolidayCommand extends Command
{
    protected $signature = 'bildabooky:summer-holiday
        {--preview : nur anzeigen, was gelöscht würde / only show what would be deleted}
        {--now : sofort ausführen, unabhängig vom Datum (fragt nach) / run immediately regardless of the date (asks first)}
        {--vorschau : alter Name von --preview / old name of --preview}
        {--jetzt : alter Name von --now / old name of --now}';

    /** Alter Name aus der Zeit vor Standard 10 / Old name from before standard 10 */
    protected $aliases = ['bildabooky:sommerferien'];
    protected $description = 'Löscht am eingestellten Tag alle Schülerkonten mit Büchern, Medien und Protokolleinträgen';

    public function handle(): int
    {
        $v = SummerHoliday::preview();
        $text = "{$v['pupils']} Schülerkonten, {$v['books']} Bücher, {$v['media']} Fotos/Tonaufnahmen"
            . ($v['classes'] ? ", {$v['classes']} Klassen" : '');

        if ($this->option('preview') || $this->option('vorschau')) {
            $this->line('Aktiv: ' . (SummerHoliday::enabled() ? 'ja, am ' . SummerHoliday::date()->format('d.m.Y') : 'nein'));
            $this->line("Würde löschen: $text");
            return self::SUCCESS;
        }

        if ($this->option('now') || $this->option('jetzt')) {
            if (! $this->confirm("Wirklich JETZT endgültig löschen: $text?")) {
                return self::FAILURE;
            }
            $b = SummerHoliday::run('command');
        } elseif (SummerHoliday::isDue()) {
            $b = SummerHoliday::run('schedule');
        } else {
            $this->line('Nichts zu tun' . (SummerHoliday::enabled() ? ' — geplant für ' . SummerHoliday::date()->format('d.m.Y') : ' — Sommerferienmodus ist aus') . '.');
            return self::SUCCESS;
        }

        $this->info("Gelöscht: {$b['pupils']} Schülerkonten, {$b['books']} Bücher, {$b['media']} Medien. Sicherung vorher: {$b['backup']}");
        return self::SUCCESS;
    }
}

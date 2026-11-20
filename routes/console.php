<?php

use Illuminate\Support\Facades\Schedule;

// Nächtliche Sicherung von Datenbank und Medien
// Nightly backup of database and media
Schedule::command('bildabooky:backup')->dailyAt('02:30');

// Sommerferienmodus: prüft täglich, ob der eingestellte Tag erreicht ist
// Summer holiday mode: checks daily whether the configured day has come
Schedule::command('bildabooky:summer-holiday')->dailyAt('03:00');

// Entfernte Fotos, Zeichnungen und Töne: 30 Tage nach dem letzten Bezug endgültig löschen
// Removed photos, drawings and sounds: delete for good 30 days after the last use
Schedule::command('bildabooky:prune-media', ['--days' => 30])->dailyAt('03:10');

// Anmelde-Sitzungen: Protokoll nur 60 Tage aufbewahren
// Sign-in sessions: keep the log for 60 days only
Schedule::call(fn () => \App\Models\ClassSession::cleanUp())->dailyAt('03:20')->name('clean-up-sessions');

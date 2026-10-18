<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Erster Admin — nur, wenn noch niemand existiert
        // First admin — only if nobody exists yet
        if (! User::exists()) {
            // Ohne ADMIN_PASSWORD ein zufälliges Startpasswort — kein bekanntes
            // „admin/admin“, mit dem sich jemand vor der ersten Anmeldung
            // zuerst anmelden könnte. Es steht einmalig im Container-Protokoll.
            // Without ADMIN_PASSWORD a random initial password — no well-known
            // “admin/admin” that someone could use to sign in before
            // the first sign-in. It appears once in the container log.
            $password = (string) config('bildabooky.admin.password');
            $random = $password === '';
            if ($random) {
                $password = User::strongPassword();
            }
            $name = strtolower(config('bildabooky.admin.username'));
            User::create([
                'first_name'           => 'Admin', 'last_name' => '',
                'username'             => $name,
                'password'             => $password,
                'role'                 => User::ADMIN,
                'must_change_password' => true,
            ]);
            $this->command?->info('Admin angelegt: ' . $name . ' (Passwort beim ersten Anmelden ändern)');
            if ($random) {
                $this->command?->warn('==============================================================');
                $this->command?->warn("  Erstes Admin-Passwort für „{$name}“: {$password}");
                $this->command?->warn('  Bitte gleich anmelden und ändern. Es wird nicht erneut angezeigt.');
                $this->command?->warn('==============================================================');
            }
        }

        if (config('bildabooky.demo') && ! SchoolClass::exists()) {
            $this->demo();
        }
    }

    private function demo(): void
    {
        $schoolClass = SchoolClass::create(['name' => '2b']);
        $sommer = User::create(['first_name' => 'Frau', 'last_name' => 'Sommer', 'username' => 'sommer',
            'password' => 'sommer123', 'role' => User::TEACHER]);
        $sommer->ledClasses()->attach($schoolClass, ['is_class_teacher' => true]);
        // zweite Lehrkraft in derselben Klasse (unterrichtet andere Fächer)
        // second teacher in the same class (teaches other subjects)
        $winter = User::create(['first_name' => 'Herr', 'last_name' => 'Winter', 'username' => 'winter',
            'password' => 'winter123', 'role' => User::TEACHER]);
        $winter->ledClasses()->attach($schoolClass);

        // Fächer: Klasse 2b hat alle, Frau Sommer und Herr Winter teilen sie sich
        // Subjects: class 2b has all of them, Ms Sommer and Mr Winter share them
        $subject = [];
        foreach ([['Deutsch', '📖'], ['Mathe', '🔢'], ['Sachunterricht', '🌱'], ['Kunst', '🎨'], ['Musik', '🎵']] as $i => [$name, $symbol]) {
            $subject[$name] = Subject::create(['name' => $name, 'symbol' => $symbol, 'position' => $i]);
        }
        $schoolClass->subjects()->sync(collect($subject)->pluck('id'));
        foreach (['Deutsch' => $sommer, 'Mathe' => $sommer, 'Sachunterricht' => $sommer, 'Kunst' => $winter, 'Musik' => $winter] as $name => $who) {
            DB::table('class_subject_teacher')->insert(['school_class_id' => $schoolClass->id, 'subject_id' => $subject[$name]->id, 'user_id' => $who->id]);
        }
        $mia = User::create(['first_name' => 'Mia', 'last_name' => 'Beispiel', 'username' => 'mia',
            'password' => 'mia', 'role' => User::PUPIL, 'school_class_id' => $schoolClass->id]);
        User::create(['first_name' => 'Ben', 'last_name' => 'Beispiel', 'username' => 'ben',
            'password' => 'ben', 'role' => User::PUPIL, 'school_class_id' => $schoolClass->id]);

        $book = Book::create(['user_id' => $mia->id, 'school_class_id' => $schoolClass->id,
            'title' => 'Der kleine Igel', 'format' => 'portrait', 'kind' => Book::KIND_OWN, 'sharing' => Book::SHARING_CLASS, 'subject_id' => $subject['Deutsch']->id]);
        $t = fn ($id, $text, $x, $y, $w, $h, $size, $extra = []) => array_merge([
            'id' => $id, 'type' => 'text', 'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h, 'r' => 0,
            'text' => $text, 'size' => $size, 'color' => '#26324a', 'bg' => '', 'font' => 'rounded',
            'bold' => false, 'italic' => false, 'align' => 'center'], $extra);
        $s = fn ($id, $char, $x, $y, $size, $r = 0) => ['id' => $id, 'type' => 'sticker', 'char' => $char,
            'x' => $x, 'y' => $y, 'w' => $size, 'h' => $size, 'r' => $r];
        $pages = [
            ['#fff6e5', [$t('a', 'Der kleine Igel', 80, 140, 840, 160, 104, ['bold' => true]),
                $t('b', 'von Mia', 80, 310, 840, 80, 52, ['color' => '#5b6b88']),
                $s('c', '🦔', 300, 520, 400), $s('d', '🍂', 130, 1050, 160, -20), $s('e', '🍄', 720, 1080, 150, 10)]],
            ['#e8f6ff', [['id' => 'h', 'type' => 'shape', 'shape' => 'circle', 'fill' => '#ffe08a',
                'x' => 700, 'y' => 60, 'w' => 220, 'h' => 220, 'r' => 0],
                $t('a', "Es war einmal ein kleiner Igel.\nEr wohnte unter einem großen Laubhaufen.", 70, 340, 860, 300, 56, ['align' => 'left']),
                $s('b', '🦔', 120, 820, 300), $s('c', '🍁', 520, 900, 200, 15)]],
            ['#eefbe9', [$t('a', 'Im Herbst sammelte er Äpfel und Pilze.', 70, 100, 860, 200, 56),
                $s('b', '🍎', 140, 420, 220), $s('c', '🍄', 400, 480, 220), $s('d', '🍎', 660, 420, 220),
                $t('e', 'Ende', 300, 1080, 400, 120, 80, ['bold' => true, 'bg' => '#ffffff', 'color' => '#2a9d8f'])]],
        ];
        foreach ($pages as $i => [$bg, $els]) {
            $book->pages()->create(['position' => $i, 'background' => $bg, 'elements' => json_encode($els)]);
        }

        // Klassenbuch: die ganze Klasse schreibt mit
        // Class book: the whole class writes in it
        $classBook = Book::create(['user_id' => $sommer->id, 'school_class_id' => $schoolClass->id,
            'title' => 'Unser Klassenausflug', 'format' => 'landscape', 'kind' => Book::KIND_CLASS, 'sharing' => Book::SHARING_PRIVATE, 'subject_id' => $subject['Sachunterricht']->id]);
        $classBook->pages()->create(['position' => 0, 'background' => '#e8f6ff', 'elements' => json_encode([
            $t('a', 'Unser Klassenausflug', 60, 60, 880, 110, 72, ['bold' => true]),
            $t('b', 'Jedes Kind gestaltet eine Seite!', 60, 190, 880, 70, 40, ['color' => '#5b6b88']),
            $s('c', '🚌', 400, 320, 220)])]);

        // Vorlage: jedes Kind legt seine eigene Version an
        // Template: each child creates their own version
        $template = Book::create(['user_id' => $sommer->id, 'school_class_id' => $schoolClass->id,
            'title' => 'Mein Lieblingstier', 'format' => 'portrait', 'kind' => Book::KIND_TEMPLATE, 'sharing' => Book::SHARING_PRIVATE, 'subject_id' => $subject['Sachunterricht']->id]);
        $template->pages()->create(['position' => 0, 'background' => '#fff6e5', 'elements' => json_encode([
            $t('a', 'Mein Lieblingstier', 80, 120, 840, 160, 96, ['bold' => true]),
            $t('b', 'von …', 80, 300, 840, 90, 52, ['color' => '#5b6b88']),
            $s('c', '❓', 350, 520, 300)])]);
        $template->pages()->create(['position' => 1, 'background' => '#eefbe9', 'elements' => json_encode([
            $t('a', 'So sieht es aus:', 70, 80, 860, 100, 60, ['align' => 'left', 'bold' => true]),
            $t('b', 'Das frisst es:', 70, 760, 860, 100, 60, ['align' => 'left', 'bold' => true])])]);
    }
}

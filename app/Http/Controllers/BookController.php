<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookLog;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'title'           => ['nullable', 'string', 'max:120'],
            'format'          => ['required', Rule::in(array_keys(Book::FORMATS))],
            // Klassenbuch und Vorlage nur für Lehrkräfte und Admins
            // Class book and template only for teachers and admins
            'kind'            => ['nullable', Rule::in($user->canLead() ? [Book::KIND_OWN, Book::KIND_CLASS, Book::KIND_TEMPLATE] : [Book::KIND_OWN])],
            'school_class_id' => ['nullable', 'integer', Rule::in($user->teachingClassIds())],
            'subject_id'      => ['nullable', 'integer'],
        ]);
        $kind = $data['kind'] ?? Book::KIND_OWN;
        if ($kind !== Book::KIND_OWN && empty($data['school_class_id'])) {
            return back()->withErrors(['school_class_id' => __('Bitte eine Klasse wählen.')]);
        }

        $classId = $user->isPupil() ? $user->school_class_id : ($data['school_class_id'] ?? null);
        // Fach nur, wenn es zur Klasse gehört (ohne Klasse: jedes Fach)
        // Subject only if it belongs to the class (without a class: any subject)
        $subjectId = null;
        if (! empty($data['subject_id'])) {
            $allowed = $classId
                ? SchoolClass::find($classId)?->subjects()->pluck('subjects.id')->all() ?? []
                : \App\Models\Subject::pluck('id')->all();
            $subjectId = in_array((int) $data['subject_id'], array_map('intval', $allowed), true) ? (int) $data['subject_id'] : null;
        }

        $book = Book::create([
            'user_id'         => $user->id,
            'school_class_id' => $classId,
            'title'           => trim($data['title'] ?? '') ?: __('Mein Buch'),
            'format'          => $data['format'],
            'kind'            => $kind,
            // ausdrücklich: der Spaltenstandard der alten Migration ist noch „privat“
            // explicitly: the column default of the old migration is still "privat"
            'sharing'         => Book::SHARING_PRIVATE,
            'subject_id'      => $subjectId,
        ]);

        // Titelseite mit dem Buchtitel
        // Title page with the book title
        $book->pages()->create([
            'position'   => 0,
            'background' => '#fff6e5',
            'elements'   => json_encode([[
                'id' => 'title', 'type' => 'text', 'x' => 80, 'y' => 120, 'w' => 840, 'h' => 200, 'r' => 0,
                'text' => $book->title, 'size' => 96, 'color' => '#26324a', 'bg' => '',
                'font' => 'rounded', 'bold' => true, 'italic' => false, 'align' => 'center',
            ], [
                'id' => 'autor', 'type' => 'text', 'x' => 80, 'y' => 340, 'w' => 840, 'h' => 80, 'r' => 0,
                'text' => $kind === Book::KIND_CLASS && $book->schoolClass
                    ? __('von der Klasse :name', ['name' => $book->schoolClass->name])
                    : __('von :name', ['name' => $user->first_name]),
                'size' => 48, 'color' => '#5b6b88', 'bg' => '',
                'font' => 'rounded', 'bold' => false, 'italic' => false, 'align' => 'center',
            ]]),
        ]);
        BookLog::record($book, $user, 'created', ['type' => $kind]);

        return redirect()->route('books.edit', $book);
    }

    public function edit(Request $request, Book $book)
    {
        $user = $request->user();
        abort_unless($book->editableBy($user), 403);
        $book->load('pages', 'owner', 'template');

        $classes = $user->isPupil()
            ? SchoolClass::whereKey($user->school_class_id)->get()
            : SchoolClass::whereIn('id', $user->teachingClassIds())->orderBy('name')->get();

        $manage = $book->manageableBy($user);
        return view('books.editor', [
            'book'    => $book,
            'classes' => $classes,
            'rights'  => [
                'manage'        => $manage,                       // Titel, Format, Teilen / title, format, sharing
                'reorderPages'  => $manage,                       // Seiten löschen/umsortieren / delete/sort pages
                'collaborative' => $book->isClassBook(),           // Abgleich mit anderen / sync with others
                'log'           => $book->logVisibleTo($user),
                'share'         => $manage && $book->kind === Book::KIND_OWN && ! $book->isVersion() && (bool) $book->school_class_id,
                'canSubmit'     => $book->isVersion() && $book->user_id == $user->id,
                // fremdes Buch (Lehrkraft hilft): Teilen aufheben nur nach Rückfrage
                // someone else's book (teacher helps): remove sharing only after confirmation
                'othersBook'    => $book->user_id != $user->id,
            ],
            'owner'    => $book->owner?->first_name,
            // Fach wählen bzw. Buch verschieben (Versionen übernehmen das Fach der Aufgabe)
            // choose a subject or move the book (versions take over the subject of the assignment)
            'subjects' => $manage && ! $book->isVersion() ? $book->allowedSubjects() : collect(),
        ]);
    }

    public function read(Request $request, Book $book)
    {
        abort_unless($book->visibleTo($request->user()), 403);
        $book->load('pages', 'owner');
        return view('books.read', [
            'book'      => $book,
            'canEdit'   => $book->editableBy($request->user()),
            'canSubmit' => $book->isVersion() && $book->user_id == $request->user()->id,
            'canReturn' => $book->returnableBy($request->user()),
        ]);
    }

    public function print(Request $request, Book $book)
    {
        abort_unless($book->visibleTo($request->user()), 403);
        $book->load('pages', 'owner');
        return view('books.print', ['book' => $book]);
    }

    /**
     * Das Buch als EINE HTML-Datei: alle Bilder und Töne eingebettet,
     * funktioniert ohne Internet und ohne Anmeldung — zum Mitnehmen nach Hause.
     *
     * The book as ONE HTML file: all images and sounds embedded,
     * works without internet and without sign-in — to take home.
     */
    public function download(Request $request, Book $book)
    {
        abort_unless($book->visibleTo($request->user()), 403);
        $book->load('pages', 'owner', 'media');

        $disk = Storage::disk('media');
        $media = [];
        foreach ($book->media as $m) {
            if ($disk->exists($m->path)) {
                $media[$m->id] = 'data:' . $m->mime . ';base64,' . base64_encode($disk->get($m->path));
            }
        }

        $html = view('books.download', [
            'book'  => $book,
            'media' => (object) $media,
            'emoji' => (object) \App\Support\Emoji::embedded($book->pages),
            'css'   => file_get_contents(public_path('css/book.css')),
            'js'    => file_get_contents(public_path('js/book.js')) . "\n" . file_get_contents(public_path('js/reader.js')),
        ])->render();

        $name = Str::slug($book->title) ?: 'book';
        return response($html, 200, [
            'Content-Type'        => 'text/html; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $name . '.html"',
        ]);
    }

    public function update(Request $request, Book $book)
    {
        $user = $request->user();
        abort_unless($book->manageableBy($user), 403);

        $data = $request->validate([
            'title'           => ['sometimes', 'string', 'max:120'],
            'format'          => ['sometimes', Rule::in(array_keys(Book::FORMATS))],
            'sharing'         => ['sometimes', Rule::in([Book::SHARING_PRIVATE, Book::SHARING_TEACHER, Book::SHARING_CLASS])],
            'school_class_id' => ['sometimes', 'nullable', 'integer'],
            'subject_id'      => ['sometimes', 'nullable', 'integer'],
        ]);
        // Fach wechseln (Buch verschieben): nur Fächer der Klasse des Buchs
        // Change subject (move book): only subjects of the book's class
        if (array_key_exists('subject_id', $data) && $data['subject_id']) {
            abort_unless($book->allowedSubjects()->pluck('id')->contains((int) $data['subject_id']), 422, __('Dieses Fach gibt es in der Klasse nicht.'));
        }

        if (array_key_exists('school_class_id', $data) && $data['school_class_id']) {
            $allowed = $user->isPupil() ? [$user->school_class_id] : $user->teachingClassIds();
            if ($book->owner->isPupil()) {
                // Bücher von Kindern bleiben in deren Klasse
                // children's books stay in their class
                $allowed = [$book->owner->school_class_id];
            }
            abort_unless(in_array($data['school_class_id'], $allowed), 422, __('Diese Klasse ist nicht erlaubt.'));
        }
        if (isset($data['sharing'])) {
            // Teilen gibt es nur bei eigenen Büchern mit Klasse; Versionen sieht die Lehrkraft ohnehin
            // Sharing exists only for own books with a class; the teacher sees versions anyway
            abort_if($book->kind !== Book::KIND_OWN || $book->isVersion(), 422);
            abort_if($data['sharing'] !== Book::SHARING_PRIVATE && ! $book->school_class_id, 422, __('Dieses Buch gehört zu keiner Klasse.'));
        }
        if (isset($data['title'])) {
            $data['title'] = trim($data['title']) ?: __('Ohne Titel');
        }

        $before = $book->only('title', 'format', 'sharing', 'school_class_id', 'subject_id');
        $book->update($data);

        // Protokoll
        // Log
        if (isset($data['title']) && $before['title'] !== $book->title) {
            // Titel wird beim Tippen mehrfach gespeichert → zusammenfassen
            // The title is saved several times while typing → merge
            BookLog::record($book, $user, 'title', ['old' => $before['title'], 'new' => $book->title], null,
                fn ($old, $new) => ['old' => $old['old'] ?? $new['old'], 'new' => $new['new']]);
        }
        if (isset($data['format']) && $before['format'] !== $book->format) {
            BookLog::record($book, $user, 'format', ['old' => $before['format'], 'new' => $book->format]);
        }
        if (isset($data['sharing']) && $before['sharing'] !== $book->sharing) {
            BookLog::record($book, $user, 'sharing', ['old' => $before['sharing'], 'new' => $book->sharing]);
        }
        if (array_key_exists('school_class_id', $data) && $before['school_class_id'] != $book->school_class_id) {
            BookLog::record($book, $user, 'class');
            // Fach passt nicht mehr zur neuen Klasse → ohne Fach
            // Subject no longer matches the new class → no subject
            if ($book->subject_id && ! $book->allowedSubjects()->pluck('id')->contains($book->subject_id)) {
                $book->update(['subject_id' => null]);
            }
        }
        if (array_key_exists('subject_id', $data) && $before['subject_id'] != $book->subject_id) {
            BookLog::record($book, $user, 'subject', ['new' => $book->subject?->name]);
            // Versionen der Kinder folgen dem Fach ihrer Aufgabe
            // Children's versions follow the subject of their assignment
            if ($book->isTemplate()) {
                Book::where('template_id', $book->id)->update(['subject_id' => $book->subject_id]);
            }
        }

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'book' => $book->only('id', 'title', 'format', 'sharing', 'school_class_id', 'subject_id')])
            : back()->with('ok', array_key_exists('subject_id', $data)
                ? __('„:titel“ liegt jetzt in :fach.', ['titel' => $book->title, 'fach' => $book->subject?->displayName() ?? __('Ohne Fach')])
                : __('Gespeichert.'));
    }

    public function duplicate(Request $request, Book $book)
    {
        $user = $request->user();
        abort_unless($book->visibleTo($user), 403);
        // aus einer Vorlage entsteht für Kinder die eigene Version
        // from a template, children get their own version
        if ($book->isTemplate() && $user->isPupil()) {
            return $this->start($request, $book);
        }
        $book->load('pages', 'media');
        $copy = $book->duplicateFor($user);
        BookLog::record($copy, $user, 'copy', ['from' => $book->title]);
        return redirect()->route('books.edit', $copy)->with('ok', __('Kopie angelegt.'));
    }

    /**
     * Vorlage: eigene Version anlegen (oder die vorhandene öffnen)
     *
     * Template: create own version (or open the existing one)
     */
    public function start(Request $request, Book $book)
    {
        $user = $request->user();
        abort_unless($book->isTemplate() && $book->visibleTo($user), 403);

        $version = Book::where('template_id', $book->id)->where('user_id', $user->id)->first();
        if (! $version) {
            $book->load('pages', 'media');
            $version = $book->duplicateFor($user, asVersion: true);
            BookLog::record($version, $user, 'version', ['template' => $book->title]);
        }
        return redirect()->route('books.edit', $version);
    }

    /**
     * Version einer Vorlage als fertig abgeben bzw. die Abgabe zurücknehmen
     *
     * Hand in a version of a template as finished, or withdraw the hand-in
     */
    public function submit(Request $request, Book $book)
    {
        $user = $request->user();
        abort_unless($book->isVersion() && $book->user_id == $user->id, 403);
        $done = $request->boolean('done');
        // erneutes Abgeben erledigt eine Rückgabe samt Nachricht
        // handing in again settles a give-back including its message
        $book->update($done
            ? ['submitted_at' => now(), 'returned_at' => null, 'return_note' => null, 'returned_by' => null]
            : ['submitted_at' => null]);
        BookLog::record($book, $user, $done ? 'submitted' : 'submission_returned');

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'submitted' => $book->submitted_at?->format('d.m.Y H:i')])
            : back()->with('ok', $done ? __('Abgegeben — deine Lehrkraft sieht jetzt, dass du fertig bist.') : __('Abgabe zurückgenommen.'));
    }

    /** Änderungsprotokoll (nur Lehrkräfte und Admins) / Change log (teachers and admins only) */
    public function log(Request $request, Book $book)
    {
        abort_unless($book->logVisibleTo($request->user()), 403);
        return view('books.log', [
            'book'    => $book->load('owner', 'schoolClass', 'template'),
            'entries' => $book->logs()->with('user:id,first_name,last_name,role')
                ->latest('updated_at')->latest('id')->paginate(100),
        ]);
    }

    /**
     * Lehrkraft gibt eine abgegebene Version ans Kind zurück — optional mit Nachricht
     *
     * Teacher gives a handed-in version back to the child — optionally with a message
     */
    public function giveBack(Request $request, Book $book)
    {
        $user = $request->user();
        abort_unless($book->returnableBy($user), 403);
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        $note = trim((string) ($data['note'] ?? '')) ?: null;

        $book->update(['submitted_at' => null, 'returned_at' => now(), 'return_note' => $note, 'returned_by' => $user->id]);
        BookLog::record($book, $user, 'returned', $note ? ['note' => $note] : []);

        return back()->with('ok', __(':name kann jetzt weiterarbeiten.', ['name' => $book->owner->first_name]));
    }

    /**
     * Vorschaubilder (erste Seite) für mehrere Bücher auf einmal — die Bibliothek
     * lädt sie erst, wenn die Karten in Sicht kommen. Nur sichtbare Bücher.
     *
     * Preview images (first page) for several books at once — the library
     * loads them only when the cards come into view. Visible books only.
     */
    public function previews(Request $request)
    {
        $ids = collect(explode(',', (string) $request->query('ids')))
            ->map(fn ($v) => (int) $v)->filter()->unique()->take(60)->values();
        $user = $request->user();
        $out = [];
        Book::with(['firstPage', 'owner:id,school_class_id,role'])->whereIn('id', $ids)->get()
            ->filter(fn (Book $b) => $b->visibleTo($user))
            ->each(function (Book $b) use (&$out) {
                $out[$b->id] = ['format' => $b->format, 'page' => $b->firstPage?->toEditorArray()];
            });
        return response()->json((object) $out)->header('Cache-Control', 'private, no-cache');
    }

    /**
     * Vorlage: Übersicht der Versionen aller Kinder der Klasse
     *
     * Template: overview of the versions of all children in the class
     */
    public function versions(Request $request, Book $book)
    {
        $user = $request->user();
        abort_unless($book->isTemplate() && $book->manageableBy($user), 403);

        $pupils = $book->schoolClass
            ? $book->schoolClass->pupils()->get()
            : collect();
        $versions = Book::where('template_id', $book->id)->with('firstPage')->get()->keyBy('user_id');

        return view('books.versions', [
            'book'      => $book,
            'rows'      => $pupils->map(fn (User $k) => ['pupil' => $k, 'version' => $versions->get($k->id)]),
            'started'   => $versions->count(),
            'submitted' => $versions->whereNotNull('submitted_at')->count(),
        ]);
    }

    public function destroy(Request $request, Book $book)
    {
        abort_unless($book->manageableBy($request->user()), 403);
        $book->deleteWithMedia();
        return redirect()->route('home')->with('ok', __('Buch gelöscht.'));
    }
}

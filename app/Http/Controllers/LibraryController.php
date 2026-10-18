<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Http\Request;

/** Startseite: die eigene Bibliothek / Home page: one's own library */
class LibraryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        // erste Seiten (Vorschau) lädt der Browser erst beim Scrollen über /previews
        // first pages (preview) are loaded by the browser only on scrolling, via /previews
        $with = ['owner:id,first_name,last_name,role', 'schoolClass:id,name', 'subject'];

        // eigene Bücher, ohne Klassenbücher und Vorlagen (die stehen bei der Klasse)
        // own books, without class books and templates (those are shown with the class)
        $mine = $user->books()->with(array_merge($with, ['template:id,title']))->where('kind', Book::KIND_OWN)
            ->latest('updated_at')->get();

        $sections = [];
        $bySubject = false;
        if ($user->isPupil() && $user->school_class_id
            && ($subjects = $user->schoolClass?->subjects()->get() ?? collect())->isNotEmpty()) {
            // Kind mit Fächern: alles nach Fach geordnet
            // Child with subjects: everything ordered by subject
            $bySubject = true;
            $cid = $user->school_class_id;
            $classBooks = Book::with($with)->where('kind', Book::KIND_CLASS)->where('school_class_id', $cid)->orderBy('title')->get();
            $templates = Book::with($with)->where('kind', Book::KIND_TEMPLATE)->where('school_class_id', $cid)->orderBy('title')->get();
            $myVersions = Book::where('user_id', $user->id)->whereIn('template_id', $templates->pluck('id'))->get()->keyBy('template_id');
            $sharedBooks = Book::with($with)->sharedIn([$cid])->where('user_id', '!=', $user->id)->latest('updated_at')->get();

            // Fächer der Klasse, dazu Fächer, die an Büchern hängen, aber nicht (mehr) zur Klasse gehören
            // Subjects of the class, plus subjects attached to books but not (or no longer) belonging to the class
            $all = collect([$mine, $classBooks, $templates, $sharedBooks])->flatten(1);
            $fremd = $all->pluck('subject')->filter()->unique('id')->reject(fn ($s) => $subjects->contains('id', $s->id));
            foreach ($subjects->concat($fremd)->push(null) as $subject) {
                $id = $subject?->id;
                $only = fn ($list) => $list->filter(fn ($b) => $b->subject_id === $id)->values();
                $groups = array_values(array_filter([
                    ['key' => 'assignment', 'symbol' => '📝', 'title' => __('Aufgaben'), 'books' => $only($templates), 'myVersions' => $myVersions],
                    ['key' => 'class', 'symbol' => '👥', 'title' => __('Klassenbücher'), 'books' => $only($classBooks)],
                    // eigene Versionen stehen bei ihrer Aufgabe, nicht noch einmal hier
                    // own versions are shown with their assignment, not again here
                    ['key' => 'mine', 'symbol' => '📘', 'title' => __('Meine Bücher'), 'books' => $only($mine)->reject(fn ($b) => $myVersions->has($b->template_id)), 'new' => true],
                    ['key' => 'shared', 'symbol' => '🏫', 'title' => __('Aus der Klasse'), 'books' => $only($sharedBooks)],
                ], fn ($g) => $g['books']->isNotEmpty() || ! empty($g['new'])));
                $count = collect($groups)->sum(fn ($g) => $g['books']->count());
                if (! $subject && $count === 0) {
                    continue;   // „Ohne Fach“ nur, wenn dort etwas liegt / “No subject” only if something is there
                }
                $sections[] = [
                    'key'       => 'subject-' . ($id ?? 'none'),
                    'symbol'    => $subject?->symbol ?? '🗂️',
                    'title'     => $subject?->name ?? __('Ohne Fach'),
                    'notice'    => $subject ? '' : __('Bücher, die noch keinem Fach zugeordnet sind. Über „⋯“ → „In anderes Fach“ verschiebst du sie.'),
                    'books'     => $all->filter(fn ($b) => $b->subject_id === $id)->values(),
                    'count'     => $count,
                    'groups'    => $groups,
                    'subject'   => $id,
                    'collapsed' => $count === 0,
                ];
            }
        } elseif ($user->isPupil()) {
            if ($cid = $user->school_class_id) {
                $classBooks = Book::with($with)->where('kind', Book::KIND_CLASS)->where('school_class_id', $cid)
                    ->latest('updated_at')->get();
                if ($classBooks->isNotEmpty()) {
                    $sections[] = ['key' => 'class-books', 'symbol' => '👥', 'title' => __('Klassenbücher'), 'notice' => __('Hier schreibt die ganze Klasse zusammen.'), 'books' => $classBooks];
                }

                $templates = Book::with($with)->where('kind', Book::KIND_TEMPLATE)->where('school_class_id', $cid)
                    ->latest('updated_at')->get();
                if ($templates->isNotEmpty()) {
                    $myVersionMap = Book::where('user_id', $user->id)->whereIn('template_id', $templates->pluck('id'))
                        ->get()->keyBy('template_id');
                    // eigene Versionen stehen bei ihrer Aufgabe, nicht noch einmal unter „Meine Bücher“
                    // own versions are shown with their assignment, not again under “My books”
                    $mine = $mine->reject(fn ($b) => $myVersionMap->has($b->template_id))->values();
                    $sections[] = ['key' => 'assignments', 'symbol' => '📝', 'title' => __('Aufgaben'), 'notice' => __('Vorlagen deiner Lehrkraft — jedes Kind gestaltet seine eigene Version.'),
                        'books' => $templates, 'myVersions' => $myVersionMap];
                }

                $sections[] = [
                    'key'    => 'class-library',
                    'symbol' => '🏫',
                    'title'  => __('Bibliothek der Klasse :name', ['name' => $user->schoolClass?->name ?? '']),
                    'notice' => __('Bücher, die mit dir geteilt wurden.'),
                    'books'  => Book::with($with)->sharedIn([$cid])->where('user_id', '!=', $user->id)->latest('updated_at')->get(),
                ];
            }
        } else {
            // Lehrkraft: je Klasse ein Bereich „Klassenbücher und Aufgaben“ und
            // ein Bereich „Bücher der Kinder“, nach Kindern gruppiert
            // Teacher: per class one section “Class books and assignments” and
            // one section “Children's books”, grouped by child
            $classes = SchoolClass::whereIn('id', $user->teachingClassIds())->orderBy('name')->get();
            foreach ($classes as $class) {
                $shared = Book::with(array_merge($with, ['versions:id,template_id,submitted_at']))
                    ->whereIn('kind', [Book::KIND_CLASS, Book::KIND_TEMPLATE])->where('school_class_id', $class->id)
                    ->orderBy('title')->get();
                [$classBooks, $templates] = $shared->partition(fn ($b) => $b->isClassBook());

                $sections[] = [
                    'key'           => 'k' . $class->id . '-shared',
                    'classTeachers' => $class->classTeachers()->get(),
                    'symbol'        => '🏫',
                    'title'         => __('Klasse :name', ['name' => $class->name]) . ' · ' . __('Klassenbücher und Aufgaben'),
                    'notice'        => __('Klassenbücher schreibt die ganze Klasse; aus Aufgaben (Vorlagen) legt jedes Kind seine eigene Version an.'),
                    'books'         => $shared,
                    'groups'        => array_values(array_filter([
                        ['key' => 'class', 'symbol' => '👥', 'title' => __('Klassenbücher'), 'books' => $classBooks->values()],
                        ['key' => 'template', 'symbol' => '📝', 'title' => __('Aufgaben (Vorlagen)'), 'books' => $templates->values()],
                    ], fn ($g) => $g['books']->isNotEmpty())),
                    'empty' => __('Noch keine. Lege über „Neues Buch“ ein Klassenbuch oder eine Aufgabe an.'),
                    'class' => $class->id,
                ];

                $pupils = $class->pupils()->orderBy('last_name')->orderBy('first_name')->get();
                $pupilBooks = Book::with(array_merge($with, ['template:id,title']))->where('kind', Book::KIND_OWN)
                    ->where(fn ($q) => $q->whereIn('user_id', $pupils->pluck('id'))->orWhere('school_class_id', $class->id))
                    ->where('user_id', '!=', $user->id)
                    ->get()->groupBy('user_id');

                // Kinder der Klasse, dazu Besitzer von Büchern der Klasse, die (nicht mehr) in ihr sind
                // Children of the class, plus owners of books of the class who are not (or no longer) in it
                $people = $pupils->concat(
                    $pupilBooks->keys()->diff($pupils->pluck('id'))->map(fn ($id) => $pupilBooks[$id]->first()->owner)->filter()
                );
                $rows = [];
                $without = [];
                foreach ($people as $pupil) {
                    $books = $pupilBooks->get($pupil->id, collect());
                    if ($books->isEmpty()) {
                        $without[] = $pupil;
                        continue;
                    }
                    // Aufgaben: abgegeben → zurückgegeben → in Arbeit; geteilt: Klasse vor Lehrkraft
                    // Assignments: handed in → given back → in progress; shared: class before teacher
                    $status = fn ($b) => $b->submitted_at ? 0 : ($b->isReturned() ? 1 : 2);
                    $assignments = $books->filter->isVersion()
                        ->sortBy([fn ($a, $b) => $status($a) <=> $status($b), fn ($a, $b) => strnatcasecmp($a->title, $b->title)])->values();
                    $sharedBooks = $books->reject->isVersion()->where('sharing', '!=', Book::SHARING_PRIVATE)
                        ->sortBy([fn ($a, $b) => ($a->sharing === Book::SHARING_CLASS ? 0 : 1) <=> ($b->sharing === Book::SHARING_CLASS ? 0 : 1),
                            fn ($a, $b) => $b->updated_at <=> $a->updated_at])->values();
                    $privateBooks = $books->reject->isVersion()->where('sharing', Book::SHARING_PRIVATE)->sortByDesc('updated_at')->values();

                    $rows[] = [
                        'pupil'     => $pupil,
                        'count'     => $books->count(),
                        'submitted' => $assignments->whereNotNull('submitted_at')->count(),
                        'groups'    => array_values(array_filter([
                            ['key' => 'assignment', 'symbol' => '📝', 'title' => __('Aufgaben'), 'books' => $assignments],
                            ['key' => 'shared', 'symbol' => '🤝', 'title' => __('Geteilt'), 'books' => $sharedBooks],
                            ['key' => 'private', 'symbol' => '🔒', 'title' => __('Nicht geteilt'), 'books' => $privateBooks],
                        ], fn ($g) => $g['books']->isNotEmpty())),
                    ];
                }

                $sections[] = [
                    'key'     => 'k' . $class->id . '-pupils',
                    'symbol'  => '🧒',
                    'title'   => __('Klasse :name', ['name' => $class->name]) . ' · ' . __('Bücher der Kinder'),
                    'notice'  => __('Nach Kindern sortiert. Du siehst als Lehrkraft alle Bücher — auch die, die (noch) nicht geteilt sind.'),
                    'books'   => $pupilBooks->flatten(1),
                    'pupils'  => $rows,
                    'without' => $without,
                    'empty'   => __('In dieser Klasse sind noch keine Kinder.'),
                    'class'   => $class->id,
                ];
            }
        }

        // Fächer je Klasse für „Neues Buch“, „In anderes Fach“ und die Filter der Lehrkraft
        // Subjects per class for “New book”, “To another subject” and the teacher's filters
        $subjectClasses = $user->isPupil()
            ? SchoolClass::whereKey($user->school_class_id)->with('subjects')->get()
            : SchoolClass::whereIn('id', $user->teachingClassIds())->with('subjects')->orderBy('name')->get();
        $subjectsByClass = [];
        foreach ($subjectClasses as $k) {
            $mySubjectIds = $user->isPupil() ? [] : $user->subjectsIn($k->id);
            $subjectsByClass[$k->id] = $k->subjects
                ->sortBy(fn ($s) => [in_array($s->id, $mySubjectIds, true) ? 0 : 1, $s->position, $s->name])
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'symbol' => $s->symbol, 'mine' => in_array($s->id, $mySubjectIds, true)])
                ->values()->all();
        }

        // Lehrkraft: Anmeldestatus je eigener Klasse oben in der Bibliothek
        // Teacher: sign-in status per own class at the top of the library
        $signIn = [];
        if (! $user->isPupil() && \App\Support\SessionRequirement::required()) {
            $activeSessions = \App\Models\ClassSession::active()->whereIn('school_class_id', $user->teachingClassIds())->get()->keyBy('school_class_id');
            foreach (SchoolClass::whereIn('id', $user->teachingClassIds())->orderBy('name')->get() as $k) {
                $signIn[] = ['class' => $k, 'session' => $activeSessions[$k->id] ?? null];
            }
        }

        // Kinder: Klasse und Klassenlehrkraft oben anzeigen
        // Children: show class and form teacher at the top
        $classInfo = null;
        if ($user->isPupil() && $user->schoolClass) {
            $classInfo = ['name' => $user->schoolClass->name, 'teachers' => $user->schoolClass->classTeachers()->get()];
        }

        return view('books.library', [
            'classInfo'       => $classInfo,
            'signIn'          => $signIn,
            'user'            => $user,
            'mine'            => $mine,
            'sections'        => $sections,
            'bySubject'       => $bySubject,
            'classes'         => SchoolClass::whereIn('id', $user->teachingClassIds())->orderBy('name')->get(),
            'subjectsByClass' => $subjectsByClass,
            'allSubjects'     => Subject::sorted()->get(['id', 'name', 'symbol']),
        ]);
    }
}

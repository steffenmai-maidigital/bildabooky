<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Klassen (anlegen/ändern/löschen nur Admin) / Classes (create/edit/delete by admin only) */
class ClassController extends Controller
{
    public function index(Request $request)
    {
        $classes = SchoolClass::whereIn('id', $request->user()->managedClassIds())
            ->withCount(['pupils', 'books'])->with('leaders', 'subjects')->orderBy('name')->get();
        // Lehrkräfte: weitere Klassen, in die sie sich selbst eintragen können
        // Teachers: further classes they can add themselves to
        $others = collect();
        if (! $request->user()->isAdmin() && self::joiningAllowed()) {
            $others = SchoolClass::whereNotIn('id', $request->user()->teachingClassIds())->with('leaders', 'subjects')->orderBy('name')->get();
        }
        $sessions = \App\Models\ClassSession::active()->whereIn('school_class_id', $classes->pluck('id'))->get()->keyBy('school_class_id');
        return view('manage.classes', [
            'classes'  => $classes,
            'sessions' => $sessions,
            'others'   => $others,
            'actor'    => $request->user(),
            'subjects' => Subject::sorted()->withCount(['classes'])->get(),
            'teachers' => $request->user()->isAdmin()
                ? User::whereIn('role', [User::TEACHER, User::ADMIN])->orderBy('last_name')->orderBy('first_name')->get()
                : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $teachers = User::whereIn('role', [User::TEACHER, User::ADMIN])->pluck('id')->map(fn ($v) => (int) $v)->all();
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:60', 'unique:school_classes,name'],
            'class_teachers'   => ['array'],
            'class_teachers.*' => ['integer', \Illuminate\Validation\Rule::in($teachers)],
        ]);
        $class = SchoolClass::create(['name' => $data['name']]);
        $class->setClassTeachers($data['class_teachers'] ?? []);
        return redirect()->route('classes.index')->with('ok', __('Klasse „:name“ angelegt.', ['name' => $data['name']]));
    }

    public function update(Request $request, SchoolClass $schoolClass)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:60',
            Rule::unique('school_classes', 'name')->ignore($schoolClass->id)]]);
        $schoolClass->update($data);
        return redirect()->route('classes.edit', $schoolClass)->with('ok', __('Klasse umbenannt in :name.', ['name' => $schoolClass->name]));
    }

    /**
     * Fächer und Lehrkräfte einer Klasse (Admin): Tabelle Fach × Lehrkraft
     *
     * Subjects and teachers of a class (admin): table subject × teacher
     */
    public function edit(SchoolClass $schoolClass)
    {
        return view('manage.class-edit', [
            'class'          => $schoolClass,
            'subjects'       => Subject::sorted()->get(),
            'activeSubjects' => $schoolClass->subjects()->pluck('subjects.id')->map(fn ($v) => (int) $v)->all(),
            // Lehrkräfte und Admins, die selbst unterrichten können
            // Teachers and admins who can teach themselves
            'teachers'=> User::whereIn('role', [User::TEACHER, User::ADMIN])->orderBy('last_name')->orderBy('first_name')->get(),
            'inClass'         => $schoolClass->leaders()->pluck('users.id')->map(fn ($v) => (int) $v)->all(),
            'classTeacherIds' => $schoolClass->classTeachers()->pluck('users.id')->map(fn ($v) => (int) $v)->all(),
            'teaching'        => $schoolClass->teaching(),
            'deletion'        => \App\Support\ClassDeletion::preview($schoolClass),
        ]);
    }

    public function saveSubjects(Request $request, SchoolClass $schoolClass)
    {
        $teacherIds = User::whereIn('role', [User::TEACHER, User::ADMIN])->pluck('id')->map(fn ($v) => (int) $v)->all();
        $subjectIds = Subject::pluck('id')->map(fn ($v) => (int) $v)->all();
        $data = $request->validate([
            'teachers'         => ['array'],
            'teachers.*'       => ['integer', \Illuminate\Validation\Rule::in($teacherIds)],
            'subjects'         => ['array'],
            'subjects.*'       => ['integer', \Illuminate\Validation\Rule::in($subjectIds)],
            'class_teachers'   => ['array'],
            'class_teachers.*' => ['integer', \Illuminate\Validation\Rule::in($teacherIds)],
            'teaching'         => ['array'],
            'teaching.*'       => ['array'],
            'teaching.*.*'=> ['integer', \Illuminate\Validation\Rule::in($teacherIds)],
        ]);
        $activeSubjects = array_values(array_unique(array_map('intval', $data['subjects'] ?? [])));
        $rows = [];
        $teachers = array_map('intval', $data['teachers'] ?? []);
        foreach ($data['teaching'] ?? [] as $subject => $people) {
            if (! in_array((int) $subject, $activeSubjects, true)) {
                continue;   // Fach nicht (mehr) in der Klasse / subject not (or no longer) in the class
            }
            foreach ($people as $p) {
                $rows[] = ['school_class_id' => $schoolClass->id, 'subject_id' => (int) $subject, 'user_id' => (int) $p];
                // wer ein Fach unterrichtet, gehört zur Klasse
                // whoever teaches a subject belongs to the class
                $teachers[] = (int) $p;
            }
        }

        $classTeacherIds = array_map('intval', $data['class_teachers'] ?? []);
        // Klassenlehrkraft gehört zur Klasse
        // form teacher belongs to the class
        $teachers = array_merge($teachers, $classTeacherIds);
        DB::transaction(function () use ($schoolClass, $activeSubjects, $rows, $teachers, $classTeacherIds) {
            $schoolClass->subjects()->sync($activeSubjects);
            $schoolClass->leaders()->sync(array_values(array_unique($teachers)));
            $schoolClass->setClassTeachers($classTeacherIds);
            DB::table('class_subject_teacher')->where('school_class_id', $schoolClass->id)->delete();
            if ($rows) {
                DB::table('class_subject_teacher')->insert(array_values(array_unique($rows, SORT_REGULAR)));
            }
        });

        return redirect()->route('classes.edit', $schoolClass)->with('ok', __('Fächer und Lehrkräfte der Klasse :name gespeichert.', ['name' => $schoolClass->name]));
    }

    public static function joiningAllowed(): bool
    {
        return \App\Models\Setting::get('teachers_can_join', '1') === '1';
    }

    /**
     * Lehrkraft: sich selbst in eine Klasse eintragen und Fächer wählen
     *
     * Teacher: add oneself to a class and choose subjects
     */
    public function join(Request $request, SchoolClass $schoolClass)
    {
        $user = $request->user();
        $joined = in_array($schoolClass->id, $user->teachingClassIds(), true);
        abort_unless($joined || self::joiningAllowed(), 403);
        $teaching = $schoolClass->teaching();
        $names = User::whereIn('id', collect($teaching)->flatten()->unique())->get()->keyBy('id');
        return view('manage.class-join', [
            'class'           => $schoolClass->load('subjects', 'leaders'),
            'joined'          => $joined,
            'mine'            => $user->subjectsIn($schoolClass->id),
            'othersBySubject' => collect($teaching)->map(fn ($ids) => collect($ids)->reject(fn ($i) => $i === $user->id)->map(fn ($i) => $names[$i]?->name)->filter()->values()),
        ]);
    }

    public function saveJoin(Request $request, SchoolClass $schoolClass)
    {
        $user = $request->user();
        abort_unless(in_array($schoolClass->id, $user->teachingClassIds(), true) || self::joiningAllowed(), 403);
        $allowed = $schoolClass->subjects()->pluck('subjects.id')->map(fn ($v) => (int) $v)->all();
        $data = $request->validate(['subjects' => ['array'], 'subjects.*' => ['integer', \Illuminate\Validation\Rule::in($allowed)]]);
        DB::transaction(function () use ($user, $schoolClass, $data) {
            $user->ledClasses()->syncWithoutDetaching([$schoolClass->id]);
            DB::table('class_subject_teacher')->where('school_class_id', $schoolClass->id)->where('user_id', $user->id)->delete();
            foreach (array_unique(array_map('intval', $data['subjects'] ?? [])) as $f) {
                DB::table('class_subject_teacher')->insert(['school_class_id' => $schoolClass->id, 'subject_id' => $f, 'user_id' => $user->id]);
            }
        });
        return redirect()->route('classes.index')->with('ok', __('Du unterrichtest jetzt in der Klasse :name.', ['name' => $schoolClass->name]));
    }

    public function leave(Request $request, SchoolClass $schoolClass)
    {
        $user = $request->user();
        DB::transaction(function () use ($user, $schoolClass) {
            $user->ledClasses()->detach($schoolClass->id);
            DB::table('class_subject_teacher')->where('school_class_id', $schoolClass->id)->where('user_id', $user->id)->delete();
        });
        return redirect()->route('classes.index')->with('ok', __('Du bist aus der Klasse :name ausgetragen.', ['name' => $schoolClass->name]));
    }

    /**
     * Klasse samt Kindern und deren Büchern löschen — nur mit eingetipptem Klassennamen
     *
     * Delete a class with its children and their books — only with the class name typed in
     */
    public function destroy(Request $request, SchoolClass $schoolClass)
    {
        if (trim((string) $request->input('confirmation')) !== $schoolClass->name) {
            return back()->withErrors(['confirmation' => __('Zur Bestätigung bitte genau den Namen der Klasse eintippen: :name', ['name' => $schoolClass->name])]);
        }
        $name = $schoolClass->name;
        try {
            $z = \App\Support\ClassDeletion::run($schoolClass);
        } catch (\Throwable $e) {
            return back()->withErrors(['confirmation' => __('Die Sicherung vor dem Löschen ist fehlgeschlagen — es wurde nichts gelöscht.')]);
        }
        return redirect()->route('classes.index')->with('ok', __('Klasse :name gelöscht: :kinder Kinder und :buecher Bücher. Sicherung: :datei', [
            'name' => $name, 'kinder' => $z['pupils'], 'buecher' => $z['books'], 'datei' => $z['backup']]));
    }
}

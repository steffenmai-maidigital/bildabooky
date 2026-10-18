@extends('layouts.app')
@section('title', __('Klasse :name bearbeiten', ['name' => $class->name]))
@section('content')
    <p><a href="{{ route('classes.index') }}">← {{ __('Klassen') }}</a></p>
    <h1>✏️ {{ __('Klasse :name bearbeiten', ['name' => $class->name]) }}</h1>

    <div class="panel">
        <h2 style="margin-top:0">🏷️ {{ __('Name der Klasse') }}</h2>
        <form method="post" action="{{ route('classes.update', $class) }}" class="row">
            @csrf @method('patch')
            <div><label for="class-name" class="sr-only">{{ __('Name der Klasse') }}</label>
                <input type="text" id="class-name" name="name" value="{{ old('name', $class->name) }}" required maxlength="60"></div>
            <div class="auto"><button class="primary">💾 {{ __('Umbenennen') }}</button></div>
        </form>
    </div>

    @if($subjects->isEmpty())
        <div class="panel"><p style="margin:0">{{ __('Es gibt noch keine Fächer.') }} <a href="{{ route('classes.index') }}#subjects">{{ __('Fächer anlegen') }}</a></p></div>
    @endif

    <form method="post" action="{{ route('classes.subjects.save', $class) }}" id="subjects-form">
        @csrf @method('put')

        <div class="panel">
            <h2 style="margin-top:0">👥 {{ __('Lehrkräfte der Klasse') }}</h2>
            <p class="hint">{{ __('Alle Lehrkräfte der Klasse sehen alle Bücher der Klasse — in allen Fächern. Mit ★ kennzeichnest du die Klassenlehrkraft (verantwortlich für die Klasse, auch mehrere möglich); die Kinder sehen sie in ihrer Bibliothek.') }}</p>
            @if($teachers->isEmpty())
                <p>{{ __('Es gibt noch keine Lehrkräfte. Lege sie unter „Benutzer“ mit der Rolle Leiter an.') }}</p>
            @else
                <div class="teacher-list">
                    @foreach($teachers as $l)
                        <div class="teacher-row">
                            <label class="check"><input type="checkbox" name="teachers[]" value="{{ $l->id }}" data-teacher="{{ $l->id }}" @checked(in_array($l->id, $inClass, true))> {{ $l->name }}</label>
                            <label class="check class-teacher-tick" title="{{ __('Klassenlehrkraft') }}"><input type="checkbox" name="class_teachers[]" value="{{ $l->id }}" data-class-teacher="{{ $l->id }}" @checked(in_array($l->id, $classTeacherIds, true))> ★ {{ __('Klassenlehrkraft') }}</label>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @if($subjects->isNotEmpty())
            <div class="panel">
                <h2 style="margin-top:0">🧩 {{ __('Fächer und wer sie unterrichtet') }}</h2>
                <p class="hint">{{ __('Links das Fach für die Klasse einschalten, rechts ankreuzen, wer es unterrichtet. Ein Fach kann mehrere Lehrkräfte haben. Wer ein Fach unterrichtet, gehört automatisch zur Klasse.') }}</p>
                <div class="table-scroll">
                <table class="list subjects-matrix">
                    <thead><tr>
                        <th>{{ __('Fach in der Klasse') }}</th>
                        @foreach($teachers as $l)<th class="matrix-head" title="{{ $l->name }}">{{ $l->name }}</th>@endforeach
                    </tr></thead>
                    <tbody>
                    @foreach($subjects as $s)
                        @php($on = in_array($s->id, $activeSubjects, true))
                        <tr class="{{ $on ? '' : 'off' }}" data-subject="{{ $s->id }}">
                            <td><label class="check"><input type="checkbox" name="subjects[]" value="{{ $s->id }}" class="subject-on" @checked($on)> <b>{{ $s->symbol }} {{ $s->name }}</b></label></td>
                            @foreach($teachers as $l)
                                <td class="matrix-cell"><input type="checkbox" name="teaching[{{ $s->id }}][]" value="{{ $l->id }}" data-teacher="{{ $l->id }}"
                                    aria-label="{{ __(':person unterrichtet :fach', ['person' => $l->name, 'fach' => $s->name]) }}"
                                    @checked(in_array($l->id, $teaching[$s->id] ?? [], true)) @disabled(! $on)></td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
            </div>
        @endif

        <div class="actions"><button class="primary">💾 {{ __('Lehrkräfte und Fächer speichern') }}</button>
            <a class="btn" href="{{ route('classes.index') }}">{{ __('Abbrechen') }}</a></div>
    </form>

    <div class="panel danger-zone">
        <h2 style="margin-top:0">🗑️ {{ __('Klasse löschen') }}</h2>
        <p>{{ __('Gelöscht werden die Klasse, ihre :kinder Kinder mit allen Büchern, Fotos und Tonaufnahmen sowie Klassenbücher und Aufgaben der Klasse — zusammen :buecher Bücher. Lehrkräfte bleiben erhalten.', ['kinder' => $deletion['pupils'], 'buecher' => $deletion['books']]) }}</p>
        <p class="hint">{{ __('Unmittelbar vorher wird automatisch eine Sicherung im Ordner „backups“ angelegt. Die Löschung selbst lässt sich nicht rückgängig machen.') }}</p>
        <form method="post" action="{{ route('classes.destroy', $class) }}"
              onsubmit="return confirm(@js(__('Klasse :name mit allen Kindern und Büchern endgültig löschen?', ['name' => $class->name])))">
            @csrf @method('delete')
            <label for="confirmation">{{ __('Zur Bestätigung den Namen der Klasse eintippen') }}</label>
            <div class="row">
                <input type="text" id="confirmation" name="confirmation" required autocomplete="off" placeholder="{{ $class->name }}">
                <button class="danger auto">🗑️ {{ __('Klasse endgültig löschen') }}</button>
            </div>
        </form>
    </div>
@endsection
@push('scripts')
<script>
    (function () {
        const form = document.getElementById('subjects-form');
        // Fach aus → Kreuze in der Zeile sperren
        // Subject off → lock the ticks in that row
        form.querySelectorAll('.subject-on').forEach((c) => c.addEventListener('change', () => {
            const row = c.closest('tr');
            row.classList.toggle('off', !c.checked);
            row.querySelectorAll('.matrix-cell input').forEach((i) => { i.disabled = !c.checked; });
        }));
        // Klassenlehrkraft gehört zur Klasse; wer austritt, ist auch nicht mehr Klassenlehrkraft
        // The class teacher belongs to the class; whoever leaves is no longer class teacher
        form.querySelectorAll('[data-class-teacher]').forEach((k) => k.addEventListener('change', () => {
            if (k.checked) form.querySelector('input[name="teachers[]"][data-teacher="' + k.dataset.classTeacher + '"]').checked = true;
        }));
        form.querySelectorAll('input[name="teachers[]"]').forEach((l) => l.addEventListener('change', () => {
            if (!l.checked) { const k = form.querySelector('[data-class-teacher="' + l.dataset.teacher + '"]'); if (k) k.checked = false; }
        }));
        // wer ein Fach unterrichtet, wird oben als Lehrkraft der Klasse angehakt
        // whoever teaches a subject is ticked above as a teacher of the class
        form.querySelectorAll('.matrix-cell input').forEach((i) => i.addEventListener('change', () => {
            if (!i.checked) return;
            const above = form.querySelector('input[name="teachers[]"][data-teacher="' + i.dataset.teacher + '"]');
            if (above) above.checked = true;
        }));
    })();
</script>
@endpush

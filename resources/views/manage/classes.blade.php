@extends('layouts.app')
@section('title', __('Klassen'))
@section('content')
    <h1>🏫 {{ __('Klassen') }}</h1>
    @if($actor->isAdmin())
        <div class="panel">
            <form method="post" action="{{ route('classes.store') }}">
                @csrf
                <div class="row">
                    <div><label for="name">{{ __('Neue Klasse') }}</label><input type="text" id="name" name="name" required maxlength="60" placeholder="{{ __('z. B. 2b') }}" value="{{ old('name') }}"></div>
                    <div class="auto"><button class="primary">➕ {{ __('Anlegen') }}</button></div>
                </div>
                @if($teachers->isNotEmpty())
                    <details class="class-teacher-choice" @if(old('class_teachers')) open @endif>
                        <summary>★ {{ __('Klassenlehrkraft festlegen (freiwillig, auch mehrere)') }}</summary>
                        <div class="check-grid">
                            @foreach($teachers as $l)
                                <label class="check"><input type="checkbox" name="class_teachers[]" value="{{ $l->id }}" @checked(in_array($l->id, old('class_teachers', [])))> {{ $l->name }}</label>
                            @endforeach
                        </div>
                    </details>
                @endif
            </form>
        </div>
    @endif

    <div class="panel">
        <div class="table-scroll">
        <table class="list classes-table">
            <thead><tr><th>{{ __('Klasse') }}</th><th>{{ __('Lehrkräfte') }}</th><th>{{ __('Fächer') }}</th><th>{{ __('Schüler') }}</th><th>{{ __('Bücher') }}</th><th>{{ __('Anmeldung') }}</th><th></th></tr></thead>
            <tbody>
            @forelse($classes as $c)
                <tr>
                    <td class="class-name">@if($actor->isAdmin())<a href="{{ route('classes.edit', $c) }}">{{ $c->name }}</a>@else{{ $c->name }}@endif</td>
                    <td class="teachers-cell">@forelse($c->leaders as $l)<span class="person">{{ $l->name }}@if($l->pivot->is_class_teacher) <span class="badge class-teacher" title="{{ __('Klassenlehrkraft') }}">★ {{ __('Klassenlehrkraft') }}</span>@endif</span>@empty — @endforelse</td>
                    <td><div class="subjects-cell">@forelse($c->subjects as $s)<span class="badge subject" title="{{ $s->name }}">{{ $s->symbol }} {{ $s->name }}</span>@empty — @endforelse</div></td>
                    <td class="count-cell">{{ $c->pupils_count }}</td>
                    <td class="count-cell">{{ $c->books_count }}</td>
                    <td>@php($sz = $sessions[$c->id] ?? null)
                        @if($sz)<span class="session-pill on" title="{{ $sz->reason }}">🔓 {{ __('bis :ende', ['ende' => $sz->ends_at->isToday() ? $sz->ends_at->format('H:i') : $sz->ends_at->format('d.m. H:i')]) }}</span>
                        @else<span class="session-pill off">🔒 {{ __('gesperrt') }}</span>@endif</td>
                    <td><div class="actions-cell">
                        @if($actor->isAdmin() || in_array($c->id, $actor->teachingClassIds(), true))<a class="btn small {{ ($sessions[$c->id] ?? null) ? '' : 'primary' }}" href="{{ route('classes.sessions', $c) }}">🔓 {{ __('Anmeldung') }}</a>@endif
                        @if(in_array($c->id, $actor->teachingClassIds(), true))<a class="btn small" href="{{ route('home') }}#section-k{{ $c->id }}-pupils">📚 {{ __('Bücher') }}</a>@endif
                        @if($actor->isAdmin())<a class="btn small" href="{{ route('classes.edit', $c) }}">✏️ {{ __('Bearbeiten') }}</a>
                        @elseif(in_array($c->id, $actor->teachingClassIds(), true))<a class="btn small" href="{{ route('classes.join', $c) }}">🧩 {{ __('Meine Fächer') }}</a>@endif
                        <a class="btn small" href="{{ route('users.index', ['class' => $c->id]) }}">👥 {{ __('Schüler') }}</a>
                        <a class="btn small" href="{{ route('users.cards', ['class' => $c->id]) }}">🖨 {{ __('Karten') }}</a>
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">{{ __('Noch keine Klassen.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        @if($actor->isAdmin())<p class="hint">{{ __('Unter „Bearbeiten“ änderst du den Namen einer Klasse, legst fest, welche Fächer es gibt und wer sie unterrichtet, oder löschst die Klasse. Eine Klasse kann mehrere Lehrkräfte haben, eine Lehrkraft mehrere Klassen.') }}</p>@endif
    </div>

    @if($others->isNotEmpty())
        <div class="panel">
            <h2 style="margin-top:0">➕ {{ __('Weitere Klassen') }}</h2>
            <p class="hint">{{ __('Unterrichtest du auch hier? Dann trag dich ein und wähle deine Fächer.') }}</p>
            <div class="more-classes">
                @foreach($others as $c)
                    <div class="more-class">
                        <b>{{ $c->name }}</b>
                        <small class="hint">{{ $c->leaders->map(fn ($l) => $l->name . ($l->pivot->is_class_teacher ? ' ★' : ''))->join(', ') ?: __('noch keine Lehrkraft') }}</small>
                        <a class="btn small primary" href="{{ route('classes.join', $c) }}">➕ {{ __('Hier unterrichte ich') }}</a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($actor->isAdmin())
        <div class="panel" id="subjects">
            <h2 style="margin-top:0">🧩 {{ __('Fächer') }}</h2>
            <p class="hint">{{ __('Fächer gelten für die ganze Schule. Welche Fächer eine Klasse hat, stellst du bei der Klasse ein. Kinder sortieren ihre Bücher nach Fächern.') }}</p>
            <form method="post" action="{{ route('subjects.store') }}" class="row subject-new">
                @csrf
                <div class="auto"><label for="subject-symbol">{{ __('Symbol') }}</label>
                    @include('manage.subject-symbol', ['name' => 'symbol', 'id' => 'subject-symbol', 'value' => '📖'])</div>
                <div><label for="subject-name">{{ __('Neues Fach') }}</label><input type="text" id="subject-name" name="name" required maxlength="60" placeholder="{{ __('z. B. Deutsch') }}"></div>
                <div class="auto"><button class="primary">➕ {{ __('Anlegen') }}</button></div>
            </form>
            @if($subjects->isNotEmpty())
                <div class="table-scroll" style="margin-top:1rem">
                <table class="list">
                    <thead><tr><th>{{ __('Reihenfolge') }}</th><th>{{ __('Fach') }}</th><th>{{ __('Klassen') }}</th><th></th></tr></thead>
                    <tbody>
                    @foreach($subjects as $s)
                        <tr>
                            <td style="white-space:nowrap">
                                <form method="post" action="{{ route('subjects.move', $s) }}" class="inline-form">@csrf<input type="hidden" name="direction" value="-1"><button class="small" title="{{ __('Nach oben') }}" @disabled($loop->first)>▲</button></form>
                                <form method="post" action="{{ route('subjects.move', $s) }}" class="inline-form">@csrf<input type="hidden" name="direction" value="1"><button class="small" title="{{ __('Nach unten') }}" @disabled($loop->last)>▼</button></form>
                            </td>
                            <td>
                                <form method="post" action="{{ route('subjects.update', $s) }}" class="row" style="flex-wrap:nowrap">
                                    @csrf @method('patch')
                                    @include('manage.subject-symbol', ['name' => 'symbol', 'id' => 'subject-symbol-' . $s->id, 'value' => $s->symbol])
                                    <input type="text" name="name" value="{{ $s->name }}" required maxlength="60" style="max-width:12rem" aria-label="{{ __('Fach') }}">
                                    <button class="small auto">💾 {{ __('Speichern') }}</button>
                                </form>
                            </td>
                            <td>{{ $s->classes_count }}</td>
                            <td>
                                <form method="post" action="{{ route('subjects.destroy', $s) }}" class="inline-form" onsubmit="return confirm(@js(__('Fach :name löschen? Die Bücher bleiben erhalten und stehen danach unter „Ohne Fach“.', ['name' => $s->name])))">
                                    @csrf @method('delete')<button class="small danger">🗑️ {{ __('Löschen') }}</button></form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
            @endif
        </div>
    @endif
    @if($actor->isAdmin())
        <div class="panel">
            <h2 style="margin-top:0">💾 {{ __('Sicherung') }}</h2>
            <p>{{ __('Jede Nacht um 2:30 Uhr wird automatisch gesichert (Ordner „backups“). Hier gibt es eine Sicherung sofort zum Herunterladen:') }}</p>
            <a class="btn" href="{{ route('backup') }}">💾 {{ __('Sicherung herunterladen') }}</a>
        </div>
    @endif
@endsection

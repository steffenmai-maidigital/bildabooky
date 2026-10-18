@extends('layouts.app')
@section('title', __('In Klasse :name unterrichten', ['name' => $class->name]))
@section('content')
    <p><a href="{{ route('classes.index') }}">← {{ __('Klassen') }}</a></p>
    <h1>🧑‍🏫 {{ __('In Klasse :name unterrichten', ['name' => $class->name]) }}</h1>

    <div class="panel">
        <p style="margin-top:0">{{ __('Als Lehrkraft der Klasse siehst du alle Bücher der Klasse, kannst Kinder anlegen, Anmeldekarten drucken und die Anmeldung freischalten.') }}</p>
        <p class="hint">{{ __('Lehrkräfte bisher:') }} {{ $class->leaders->map(fn ($l) => $l->name . ($l->pivot->is_class_teacher ? ' (★ ' . __('Klassenlehrkraft') . ')' : ''))->join(', ') ?: '—' }}</p>

        <form method="post" action="{{ route('classes.join.save', $class) }}">
            @csrf @method('put')
            <label>{{ __('Welche Fächer unterrichtest du hier?') }}</label>
            @if($class->subjects->isEmpty())
                <p class="hint">{{ __('Für diese Klasse sind noch keine Fächer eingestellt — das macht der Admin. Eintragen kannst du dich trotzdem.') }}</p>
            @else
                <div class="check-grid">
                    @foreach($class->subjects as $s)
                        <label class="check"><input type="checkbox" name="subjects[]" value="{{ $s->id }}" @checked(in_array($s->id, $mine, true))>
                            <span>{{ $s->symbol }} {{ $s->name }}
                                @if(($othersBySubject[$s->id] ?? collect())->isNotEmpty())<small class="hint" style="display:block">{{ __('auch: :namen', ['namen' => $othersBySubject[$s->id]->join(', ')]) }}</small>@endif
                            </span></label>
                    @endforeach
                </div>
            @endif
            <div class="actions"><button class="primary">{{ $joined ? '💾 ' . __('Speichern') : '➕ ' . __('Eintragen') }}</button>
                <a class="btn" href="{{ route('classes.index') }}">{{ __('Abbrechen') }}</a></div>
        </form>
    </div>

    @if($joined)
        <div class="panel danger-zone">
            <h2 style="margin-top:0">🚪 {{ __('Aus der Klasse austragen') }}</h2>
            <p class="hint">{{ __('Danach siehst du die Bücher dieser Klasse nicht mehr. Deine eigenen Bücher bleiben.') }}</p>
            <form method="post" action="{{ route('classes.leave', $class) }}" onsubmit="return confirm(@js(__('Wirklich aus der Klasse :name austragen?', ['name' => $class->name])))">
                @csrf @method('delete')<button class="danger">🚪 {{ __('Austragen') }}</button>
            </form>
        </div>
    @endif
@endsection

@extends('layouts.app')
@section('title', $user->name)
@section('content')
    <p><a href="{{ route('users.index') }}">← {{ __('Benutzer') }}</a></p>
    <h1>{{ $user->name }}</h1>

    <div class="panel">
        <form method="post" action="{{ route('users.update', $user) }}">
            @csrf @method('patch')
            @include('manage.user-fields')
            @if($user->hasCard())<p class="hint">{{ __('Haken entfernen löscht die hinterlegte Karte. Zum Hinterlegen einer neuen Karte das Passwort neu vergeben.') }}</p>@endif
            <div class="actions"><button class="primary">💾 {{ __('Speichern') }}</button></div>
        </form>
    </div>

    <div class="panel">
        <h2 style="margin-top:0">🔑 {{ __('Zugang') }}</h2>
        <div class="row" style="align-items:flex-start">
            @if(! $user->isLocal())
                <div><b>🏫 {{ __('Schulkonto') }}</b><p class="hint">{{ __('Meldet sich mit dem Schulkonto aus dem Verzeichnis an. Passwort und Rolle (Lehrkraft/Admin) kommen von dort; Klassen und Fächer ordnest du hier zu.') }}</p></div>
            @elseif(! $user->is($actor))
            <form method="post" action="{{ route('users.password', $user) }}">
                @csrf
                <b>{{ __('Neues Passwort vergeben') }}</b>
                <p class="hint">{{ $user->isPupil() ? __('Ein leichtes Passwort wie „tiger47“.') : __('Ein Startpasswort, das bei der Anmeldung geändert werden muss.') }}</p>
                <label class="check"><input type="checkbox" name="card" value="1" @checked($user->hasCard())> {{ __('Anmeldekarte hinterlegen') }}</label>
                <button>🔑 {{ __('Passwort zurücksetzen') }}</button>
            </form>
            @endif
            @if($user->hasCard())
                <div><b>{{ __('Anmeldekarte') }}</b><p class="hint">{{ __('Ist hinterlegt und kann jederzeit gedruckt werden.') }}</p>
                    <a class="btn" href="{{ route('users.cards', ['user' => $user->id]) }}">🖨 {{ __('Karte drucken') }}</a></div>
            @endif
            <div>
                <b>{{ __('Zwei-Faktor') }}</b>
                @if($user->hasTwoFactor())
                    <p class="hint">{{ __('Aktiv. Zurücksetzen, wenn das Handy verloren ist.') }}</p>
                    <form method="post" action="{{ route('users.2fa', $user) }}" onsubmit="return confirm(@js(__('Zwei-Faktor für :name zurücksetzen?', ['name' => $user->name])))">
                        @csrf @method('delete')<button>🔐 {{ __('Zwei-Faktor zurücksetzen') }}</button></form>
                @else
                    <p class="hint">{{ __('Nicht eingerichtet. Jede Person richtet das selbst unter „Konto“ ein.') }}</p>
                @endif
            </div>
        </div>
    </div>

    @unless($user->is($actor))
    <div class="panel">
        <h2 style="margin-top:0">🗑️ {{ __('Löschen') }}</h2>
        <p>{{ __('Löscht :name und alle :n Bücher samt Fotos und Tonaufnahmen. Tipp: Bücher vorher über „Als Datei mitnehmen“ sichern.', ['name' => $user->name, 'n' => $user->books()->count()]) }}</p>
        <form method="post" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm(@js(__(':name und alle Bücher endgültig löschen?', ['name' => $user->name])))">
            @csrf @method('delete')<button class="danger">🗑️ {{ __('Endgültig löschen') }}</button></form>
    </div>
    @endunless
@endsection

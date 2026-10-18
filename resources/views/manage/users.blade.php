@extends('layouts.app')
@section('title', __('Benutzer'))
@section('content')
    <h1>👥 {{ __('Benutzer') }}</h1>

    @if($credentials)
        <div class="panel">
            <div class="lib-head">
                <h2 style="margin:0">🔑 {{ __('Zugangsdaten') }}</h2>
                <button class="primary small" onclick="printCards()">🖨 {{ __('Karten drucken') }}</button>
            </div>
            <p class="hint">{{ __('Diese Passwörter werden nur jetzt angezeigt — außer bei Karten, die hinterlegt wurden.') }}</p>
            @include('partials.credentials')
        </div>
    @endif

    <div class="panel">
        <form method="get" class="row" style="margin-bottom:.8rem">
            <div><label for="search">{{ __('Suche') }}</label><input type="search" id="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Name oder Benutzername') }}"></div>
            <div><label for="f-class">{{ __('Klasse') }}</label>
                <select id="f-class" name="class"><option value="">{{ __('alle') }}</option>
                    @foreach($classes as $c)<option value="{{ $c->id }}" @selected(request('class') == $c->id)>{{ $c->name }}</option>@endforeach
                </select></div>
            @if($actor->isAdmin())
            <div><label for="f-role">{{ __('Rolle') }}</label>
                <select id="f-role" name="role"><option value="">{{ __('alle') }}</option>
                    @foreach(\App\Models\User::ROLES as $k => $v)<option value="{{ $k }}" @selected(request('role') === $k)>{{ __($v) }}</option>@endforeach
                </select></div>
            @endif
            <div class="auto"><button>🔍 {{ __('Filtern') }}</button></div>
            @if(request('class'))
                <div class="auto"><a class="btn" href="{{ route('users.cards', ['class' => request('class')]) }}">🖨 {{ __('Karten der Klasse') }}</a></div>
            @endif
        </form>

        <div class="table-scroll">
        <table class="list">
            <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Benutzername') }}</th><th>{{ __('Rolle') }}</th><th>{{ __('Klasse') }}</th><th>{{ __('Bücher') }}</th><th>{{ __('Zuletzt da') }}</th><th></th></tr></thead>
            <tbody>
            @forelse($users as $u)
                <tr>
                    <td><b>{{ $u->last_name }}</b>{{ $u->last_name ? ',' : '' }} {{ $u->first_name }}
                        @if($u->hasTwoFactor()) <span title="{{ __('Zwei-Faktor aktiv') }}">🔐</span>@endif
                        @unless($u->isLocal()) <span class="badge kind" title="{{ __('Meldet sich mit dem Schulkonto an') }}">🏫 {{ __('Schulkonto') }}</span>@endunless
                        @if($u->hasCard()) <span title="{{ __('Anmeldekarte hinterlegt') }}">🪪</span>@endif</td>
                    <td><code>{{ $u->username }}</code></td>
                    <td><span class="badge {{ $u->role }}">{{ $u->roleLabel() }}</span></td>
                    <td>{{ $u->isTeacher() ? $u->ledClasses->pluck('name')->join(', ') : $u->schoolClass?->name }}</td>
                    <td>{{ $u->books_count }}</td>
                    <td class="hint">{{ $u->last_login_at?->format('d.m.Y H:i') ?? '—' }}</td>
                    <td><a class="btn small" href="{{ route('users.edit', $u) }}">✏️ {{ __('Bearbeiten') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">{{ __('Keine Benutzer gefunden.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @if($classes->isEmpty())
        <div class="flash info">
            @if($actor->isAdmin()){{ __('Lege zuerst unter „Klassen“ eine Klasse an.') }} <a href="{{ route('classes.index') }}">🏫 {{ __('Klassen') }}</a>
            @else {{ __('Dir ist noch keine Klasse zugeordnet. Bitte wende dich an den Admin.') }}@endif
        </div>
    @else
    <details class="panel collapsible" @if($users->isEmpty() || $errors->any()) open @endif>
        <summary><h2>➕ {{ __('Schüler anlegen') }}</h2></summary>
        <form method="post" action="{{ route('users.bulk') }}">
            @csrf
            <div class="row">
                <div>
                    <label for="bulk-class">{{ __('Klasse') }}</label>
                    <select id="bulk-class" name="school_class_id" required>
                        @foreach($classes as $c)<option value="{{ $c->id }}" @selected(request('class') == $c->id)>{{ $c->name }}</option>@endforeach
                    </select>
                </div>
            </div>
            <label for="names">{{ __('Namen — eine Zeile je Kind: Vorname Nachname') }}</label>
            <textarea id="names" name="names" required placeholder="Mia Sommer&#10;Ben Yilmaz&#10;Lea Novak"></textarea>
            <label class="check"><input type="checkbox" name="card" value="1"> {{ __('Anmeldekarte hinterlegen (Passwort verschlüsselt speichern, Karte jederzeit neu druckbar)') }}</label>
            <p class="hint">{{ __('Benutzername und ein leichtes Passwort (z. B. „tiger47“) werden automatisch vergeben.') }}</p>
            <button class="primary">➕ {{ __('Anlegen') }}</button>
        </form>
    </details>
    @endif

    <details class="panel collapsible">
        <summary><h2>👤 {{ $actor->isAdmin() ? __('Einzelne Person anlegen (auch Lehrkräfte und Admins)') : __('Einzelne Person anlegen') }}</h2></summary>
        <form method="post" action="{{ route('users.store') }}">
            @csrf
            @include('manage.user-fields', ['user' => null])
            <div class="actions"><button class="primary">➕ {{ __('Anlegen') }}</button></div>
        </form>
    </details>

@endsection
@push('scripts')
<script>
    function printCards() {
        document.body.classList.add('print-only-cards');
        window.print();
        document.body.classList.remove('print-only-cards');
    }
</script>
@endpush

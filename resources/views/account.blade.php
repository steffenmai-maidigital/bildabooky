@extends('layouts.app')
@section('title', __('Mein Konto'))
@section('content')
    <h1>👤 {{ __('Mein Konto') }}</h1>
    <div class="panel">
        <b>{{ $user->name }}</b>
        @unless($user->isPupil()) · <span class="badge {{ $user->role }}">{{ $user->roleLabel() }}</span>@endunless
        @if($user->schoolClass) · {{ __('Klasse') }} {{ $user->schoolClass->name }} @endif
        <div class="hint">{{ __('Benutzername') }}: <code>{{ $user->username }}</code></div>
    </div>

    @if(\App\Models\Setting::get('fox_enabled', '1') === '1')
    <div class="panel">
        <h2 style="margin-top:0">🦊 {{ __('Fuchs-Helfer') }}</h2>
        <form method="post" action="{{ route('account.fox') }}" class="row" style="align-items:center">
            @csrf @method('put')
            <p class="auto" style="margin:0;flex:1 1 260px">{{ $user->showsFox() ? __('Der Fuchs zeigt dir Tipps und muntert dich auf.') : __('Der Fuchs macht gerade Pause.') }}</p>
            <input type="hidden" name="fox" value="{{ $user->showsFox() ? 0 : 1 }}">
            <button class="auto {{ $user->showsFox() ? '' : 'primary' }}">{{ $user->showsFox() ? __('Fuchs ausblenden') : __('Fuchs anzeigen') }}</button>
        </form>
    </div>
    @endif

    @unless($user->isLocal())
    <div class="panel">
        <h2 style="margin-top:0">🏫 {{ __('Schulkonto') }}</h2>
        <p style="margin:0">{{ __('Du meldest dich mit deinem Schulkonto an. Das Passwort änderst du dort, nicht in BilDaBooky.') }}</p>
    </div>
    @else
    <div class="panel">
        <h2 style="margin-top:0">🔑 {{ __('Passwort ändern') }}</h2>
        <form method="post" action="{{ route('account.password') }}" style="max-width:420px">
            @csrf @method('put')
            <label for="current">{{ __('Bisheriges Passwort') }}</label>
            <input type="password" id="current" name="current" required autocomplete="current-password">
            <label for="password">{{ __('Neues Passwort') }}</label>
            <input type="password" id="password" name="password" required autocomplete="new-password"
                   minlength="{{ $user->isPupil() ? 4 : 8 }}">
            <label for="password_confirmation">{{ __('Neues Passwort wiederholen') }}</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
            <div class="actions"><button class="primary">💾 {{ __('Passwort speichern') }}</button></div>
        </form>
    </div>
    @endunless

    {{-- Zwei-Faktor nach Richtlinie (Einstellungen → 8); bei Kindern ersetzt ihn die Klassen-Sitzung

         Two-factor according to policy (settings → 8); for pupils the class session replaces it --}}
    @if($user->twoFactorAllowed())
    <div class="panel">
        <h2 style="margin-top:0">🔐 {{ __('Zwei-Faktor-Anmeldung') }}</h2>
        @if($codes)
            <div class="flash info">
                <b>{{ __('Wiederherstellungscodes — jetzt notieren oder ausdrucken!') }}</b><br>
                {{ __('Jeder Code funktioniert genau einmal, falls das Handy fehlt. Sie werden nur jetzt angezeigt.') }}
            </div>
            <div class="codes">@foreach($codes as $c)<div>{{ $c }}</div>@endforeach</div>
        @endif

        @if($user->hasTwoFactor())
            <p><span class="badge ok">{{ __('eingeschaltet') }}</span>
                {{ __('seit :datum. Bei jeder Anmeldung wird zusätzlich ein Code aus der App abgefragt.', ['datum' => $user->two_factor_confirmed_at->format('d.m.Y')]) }}
                {{ __('Noch :n Wiederherstellungscodes übrig.', ['n' => count($user->two_factor_recovery_codes ?? [])]) }}</p>
            <div class="row">
                <form method="post" action="{{ route('account.2fa.codes') }}">
                    @csrf
                    <label>{{ $user->isLocal() ? __('Neue Wiederherstellungscodes (Passwort)') : __('Neue Wiederherstellungscodes (aktueller Code aus der App)') }}</label>
                    <div class="row"><input type="password" name="password" required autocomplete="current-password"><button class="auto">🔄 {{ __('Neu erzeugen') }}</button></div>
                </form>
                @unless($user->twoFactorRequired())
                    <form method="post" action="{{ route('account.2fa.disable') }}">
                        @csrf @method('delete')
                        <label>{{ $user->isLocal() ? __('Ausschalten (Passwort)') : __('Ausschalten (aktueller Code aus der App)') }}</label>
                        <div class="row"><input type="password" name="password" required autocomplete="current-password"><button class="danger auto">{{ __('Ausschalten') }}</button></div>
                    </form>
                @endunless
            </div>
        @elseif($setup)
            <ol>
                <li>{{ __('Authenticator-App öffnen (z. B. Aegis, FreeOTP, Google oder Microsoft Authenticator).') }}</li>
                <li>{{ __('Diesen QR-Code scannen — oder den Schlüssel von Hand eingeben.') }}</li>
                <li>{{ __('Den angezeigten 6-stelligen Code unten eintragen.') }}</li>
            </ol>
            <div class="row" style="align-items:center">
                <div class="auto qr">{!! $setup['qr'] !!}</div>
                <div>
                    <div class="hint">{{ __('Schlüssel') }}</div>
                    <code style="font-size:1.05rem;word-break:break-all">{{ $setup['secret'] }}</code>
                    <form method="post" action="{{ route('account.2fa.confirm') }}">
                        @csrf
                        <label for="code">{{ __('Code aus der App') }}</label>
                        <div class="row"><input type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="10" required>
                            <button class="primary auto">{{ __('Einschalten') }}</button></div>
                    </form>
                </div>
            </div>
        @else
            <p>{{ __('Schützt das Konto zusätzlich mit einem Code aus einer Authenticator-App auf dem Handy.') }}
                @if($user->twoFactorRequired())<b>{{ __('Für deine Gruppe ist das vorgeschrieben.') }}</b>@else {{ __('Empfohlen für Lehrkräfte und Admins.') }}@endif</p>
            <form method="post" action="{{ route('account.2fa.start') }}">@csrf<button class="blue">🔐 {{ __('Zwei-Faktor einrichten') }}</button></form>
        @endif
    </div>
    @endif
@endsection

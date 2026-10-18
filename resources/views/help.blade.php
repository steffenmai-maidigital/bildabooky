@extends('layouts.app')
@section('title', __('Hilfe'))
@section('content')
    <h1>❓ {{ __('Hilfe') }}</h1>

    {{-- 1 · Kurzanleitung (Standard 03) / Quick guide (Standard 03) --}}
    <div class="panel">
        <h2 style="margin-top:0">📘 {{ __('Kurzanleitung') }}</h2>
        <ul class="help-list">
            <li>📚 {{ __('In der Bibliothek auf „Neues Buch“ tippen, einen Namen und eine Form wählen.') }}</li>
            <li>➕ {{ __('Links (am Handy unten) etwas hinzufügen: Text, Foto, Kamera, Malen, Ton, Sticker, Form oder Seitenfarbe.') }}</li>
            <li>👆 {{ __('Etwas antippen, um es zu verschieben. Mit dem runden Griff unten rechts änderst du die Größe, mit dem Pfeil oben drehst du.') }}</li>
            <li>✏️ {{ __('Einen Text zweimal antippen, um ihn zu schreiben.') }}</li>
            <li>↶ {{ __('Vertippt? Oben auf „Rückgängig“ tippen. Gespeichert wird automatisch.') }}</li>
            <li>📖 {{ __('Mit „Lesen“ blätterst du durch dein Buch und hörst die Töne.') }}</li>
            <li>💾 {{ __('Über „⋯“ beim Buch kannst du es drucken oder als Datei mit nach Hause nehmen.') }}</li>
            @if($user->canLead())
                <li>👥 {{ __('Unter „Benutzer“ legst du ganze Klassenlisten auf einmal an und druckst Anmeldekarten.') }}</li>
                <li>🏫 {{ __('Unter „Klassen“ findest du die Bücher deiner Klasse, schaltest die Anmeldung frei und kannst Karten neu drucken.') }}</li>
                <li>🔐 {{ __('Unter „Konto“ richtest du die Zwei-Faktor-Anmeldung ein und schaltest den Fuchs für dich ein oder aus.') }}</li>
                <li>🔒 {{ __('Kamera und Mikrofon funktionieren nur, wenn BilDaBooky über https:// aufgerufen wird.') }}</li>
            @endif
            @if($user->isAdmin())
                <li>🎨 {{ __('Unter „Einstellungen“ pflegst du Schule, Domain, Farben, Logo, Anmeldung (Sitzungspflicht, Zwei-Faktor), Schulverzeichnis und Sommerferienmodus.') }}</li>
            @endif
        </ul>
        <p class="hint" style="margin-bottom:0">{{ __('Ausführlich im') }} <a href="{{ route('help.doc', 'user') }}">{{ __('Benutzerhandbuch') }}</a>.</p>
    </div>

    {{-- 2 · System / System --}}
    <div class="panel">
        <h2 style="margin-top:0">🖥️ {{ __('System') }}</h2>
        <table class="list">
            <tr><th style="width:200px">{{ __('Produkt') }}</th><td>BilDaBooky · <span class="edition {{ $license ? 'supported' : '' }}">{{ \App\Support\License::edition() }}</span></td></tr>
            <tr><th>{{ __('Version') }}</th><td>{{ config('version.number') }}</td></tr>
            <tr><th>{{ __('Build') }}</th><td><code>{{ config('version.build') }}</code></td></tr>
            @if(\App\Models\Setting::get('app_updated_at'))
                <tr><th>{{ __('Zuletzt aktualisiert') }}</th><td>{{ \Illuminate\Support\Carbon::parse(\App\Models\Setting::get('app_updated_at'))->format('d.m.Y H:i') }}</td></tr>
            @endif
            <tr><th>PHP / Laravel</th><td>{{ PHP_VERSION }} / {{ app()->version() }}</td></tr>
            <tr><th>{{ __('Anmeldung') }}</th><td>{{ __('eigene Konten') }}@if($directory) + {{ __('Schulverzeichnis für Lehrkräfte und Admins') }}@endif</td></tr>
            <tr><th>{{ __('Dein Konto') }}</th><td>{{ $user->origin() }}@unless($user->isPupil()) · {{ __('Zwei-Faktor') }}: {{ $user->hasTwoFactor() ? __('eingeschaltet') : __('nicht eingerichtet') }}@endunless · <a href="{{ route('account') }}">{{ __('Mein Konto') }}</a></td></tr>
            <tr><th>{{ __('Software-Lizenz') }}</th><td>GNU AGPL-3.0 — {{ __('freie Software, darf von allen genutzt und verändert werden') }}</td></tr>
            <tr><th>{{ __('Sticker und Symbole') }}</th><td><a href="https://github.com/jdecked/twemoji" target="_blank" rel="noopener">Twemoji</a> © Twitter, Inc {{ __('und weitere Mitwirkende') }} — <a href="https://creativecommons.org/licenses/by/4.0/" target="_blank" rel="noopener">CC-BY 4.0</a></td></tr>
            <tr><th>{{ __('Systemmodus') }}</th><td>{{ config('bildabooky.demo') ? __('Demo-Modus aktiv') : __('Produktivsystem') }}@if(config('bildabooky.test_system')) · <strong style="color:var(--danger)">TESTSYSTEM</strong>@endif</td></tr>
        </table>
    </div>

    {{-- 3 · Lizenz / Licence --}}
    <div class="panel">
        <h2 style="margin-top:0">📜 {{ __('Lizenz') }}</h2>
        @if($license)
            <table class="list">
                <tr><th style="width:200px">{{ __('Lizenzname') }}</th><td>{{ __($license['name']) }}</td></tr>
                @if($licenseCompany)<tr><th>{{ __('Lizenziert für') }}</th><td>{{ $licenseCompany }}</td></tr>@endif
                <tr><th>{{ __('Kundennummer') }}</th><td>{{ $license['customer_no'] }}</td></tr>
                <tr><th>{{ __('Anzahl') }}</th><td>{{ __($license['count_display']) }}</td></tr>
                <tr><th>{{ __('Lizenzcode') }}</th><td><code>{{ $license['key'] }}</code></td></tr>
                <tr><th>{{ __('Support bis') }}</th><td>
                    @if($license['expired'])<span style="color:var(--danger);font-weight:700">{{ $license['support_end']->format('d.m.Y') }} – {{ __('Support abgelaufen') }}</span>
                    @else{{ $license['support_end']->format('d.m.Y') }}@endif
                </td></tr>
            </table>
            @if($license['expired'])
                <p class="hint">{{ __('Der Ablauf des Supports hat keine funktionalen Auswirkungen – die Anwendung läuft uneingeschränkt weiter.') }}</p>
            @endif
        @elseif($rawKey)
            <p style="color:var(--danger)">{{ __('Der hinterlegte Lizenzschlüssel entspricht nicht dem gültigen Schema.') }}</p>
        @else
            <p>{{ __('Community Edition — freie Open-Source-Fassung, vollständig und ohne Einschränkungen nutzbar.') }}</p>
            <p class="hint" style="margin-bottom:0">{{ __('Es ist keine Lizenz gepflegt. Administratoren können Schule und Lizenzschlüssel unter Einstellungen → Lizenz hinterlegen.') }}</p>
        @endif
    </div>

    {{-- 4 · Handbücher / Manuals --}}
    <div class="panel">
        <h2 style="margin-top:0">📚 {{ __('Handbücher') }}</h2>
        <table class="list">
            @foreach($docs as $key => [$label, $file, $adminOnly, $short])
                @continue($adminOnly && ! $isAdmin)
                @continue($key === 'changelog')
                <tr><td><a href="{{ route('help.doc', $key) }}">{{ __($label) }}</a></td><td class="hint">{{ __($short) }}</td></tr>
            @endforeach
        </table>
        <p class="hint" style="margin-bottom:0">{{ __('Die Handbücher liegen auf Deutsch vor.') }}</p>
    </div>

    {{-- 5 · Änderungshistorie / Change history --}}
    <div class="panel">
        <h2 style="margin-top:0">📋 {{ __('Änderungshistorie') }}</h2>
        <p style="margin:0">{{ __('Was in welcher Version neu ist, steht im') }} <a href="{{ route('help.doc', 'changelog') }}">{{ __('Changelog') }}</a> — {{ __('aktuell installiert') }}: {{ config('version.number') }}.</p>
    </div>

    {{-- 6 · Letzte Fehler (Admins) / Recent errors (admins) --}}
    @if($recentErrors !== null)
        <div class="panel">
            <h2 style="margin-top:0">🐞 {{ __('Letzte Fehler (nur für Administratoren)') }}</h2>
            @if($recentErrors['count'] === 0)
                <p class="hint" style="margin:0">{{ __('Keine Fehler in den letzten 30 Tagen – alles in Ordnung.') }}</p>
            @else
                <p class="hint" style="margin-top:0">{{ __('Die letzten Fehlereinträge aus dem Anwendungslog (gekürzt). Bei Supportanfragen bitte diese Zeilen mitschicken.') }}
                    {{ __(':n Fehler in den letzten 30 Tagen.', ['n' => $recentErrors['count']]) }}</p>
                @foreach($recentErrors['lines'] as $line)
                    <pre class="log-line">{{ $line }}</pre>
                @endforeach
                <form method="post" action="{{ route('help.clear-log') }}" style="margin-top:.6rem"
                      onsubmit="return confirm(@js(__('Das gesamte Anwendungsprotokoll leeren?')))">
                    @csrf @method('delete')<button class="small">🧹 {{ __('Protokoll leeren') }}</button>
                </form>
            @endif
        </div>
    @endif

    {{-- 7 · Support / Support --}}
    <div class="panel">
        <h2 style="margin-top:0">💬 {{ __('Support') }}</h2>
        <p>{{ __('Bei Fragen oder Problemen wende dich bitte an die Schul-IT bzw. den Admin. Halte dafür die Kundennummer aus der Lizenz und die Build-Nummer bereit.') }}</p>
        <table class="list">
            @if(config('bildabooky.github'))
                <tr><th style="width:200px">🐙 GitHub</th><td>{{ __('Quellcode, Fehler melden und Ideen einbringen') }}:
                    <a href="{{ config('bildabooky.github') }}" target="_blank" rel="noopener">{{ preg_replace('#^https?://#', '', config('bildabooky.github')) }}</a>
                    · <a href="{{ rtrim(config('bildabooky.github'), '/') }}/issues" target="_blank" rel="noopener">{{ __('Fehler melden') }}</a></td></tr>
            @endif
            @if(config('bildabooky.project_url'))
                <tr><th>🌍 {{ __('Projektseite') }}</th><td>{{ __('Neuigkeiten, Anleitungen und unterstützte Version') }}:
                    <a href="{{ config('bildabooky.project_url') }}" target="_blank" rel="noopener">{{ preg_replace('#^https?://#', '', config('bildabooky.project_url')) }}</a></td></tr>
            @endif
            @if(config('bildabooky.community') && ! in_array(rtrim(config('bildabooky.community'), '/'), [rtrim(config('bildabooky.github'), '/'), rtrim(config('bildabooky.project_url'), '/')], true))
                <tr><th>👥 {{ __('Community') }}</th><td><a href="{{ config('bildabooky.community') }}" target="_blank" rel="noopener">{{ config('bildabooky.community') }}</a></td></tr>
            @endif
        </table>
        <p class="hint" style="margin-bottom:0">{{ __('Bitte keine Namen, Fotos oder Zugangsdaten von Kindern in öffentliche Fehlermeldungen schreiben.') }}</p>
    </div>
@endsection

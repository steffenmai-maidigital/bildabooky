<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'BilDaBooky') · BilDaBooky</title>
    <link rel="icon" href="{{ \App\Support\Branding::logoUrl() }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ config('version.build') }}">
    <link rel="stylesheet" href="{{ asset('css/book.css') }}?v={{ config('version.build') }}">
    <style>{!! \App\Support\Branding::css() !!}</style>
    <meta name="symbol-base" content="{{ rtrim(asset(''), '/') }}">
    <script src="{{ asset('js/symbols.js') }}?v={{ config('version.build') }}" defer></script>
    <script>window.BDB_TEXTS = @json((object) \App\Support\Lang::forJs());</script>
    @hasSection('fullscreen')
    @else
    <script>
        // Hell/Dunkel (Standard 03): gespeicherte Wahl, sonst Systemeinstellung.
        // Light/dark (Standard 03): saved choice, otherwise the system setting.
        // Editor und Leseansicht (Vollbild, Abschnitt „fullscreen“) bleiben immer hell.
        // Editor and reading view (full screen, section "fullscreen") always stay light.
        (function () {
            var t = null; try { t = localStorage.getItem('theme'); } catch (e) {}
            document.documentElement.dataset.theme = t || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        })();
    </script>
    @endif
    @stack('head')
</head>
<body class="@yield('body-class')">
@php($u = auth()->user())
@hasSection('fullscreen')
    @yield('fullscreen')
@else
@if(config('bildabooky.test_system'))<div class="test-banner">TESTSYSTEM</div>@endif
<header class="top">
    <a class="brand" href="{{ route('home') }}">
        <img src="{{ \App\Support\Branding::logoUrl() }}" alt="">
        <span>{{ \App\Support\Branding::school() }}<small>BilDaBooky</small></span>
    </a>
    @if($u)
        <nav>
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">📚 {{ __('Bibliothek') }}</a>
            @if($u->canLead())
                <a href="{{ route('classes.index') }}" class="{{ request()->routeIs('classes.*') ? 'active' : '' }}">🏫 {{ __('Klassen') }}</a>
                <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">👥 {{ __('Benutzer') }}</a>
            @endif
            @if($u->isAdmin())
                <a href="{{ route('settings') }}" class="{{ request()->routeIs('settings') ? 'active' : '' }}">🎨 {{ __('Einstellungen') }}</a>
            @endif
            <a href="{{ route('account') }}" class="{{ request()->routeIs('account') ? 'active' : '' }}">🔑 {{ __('Konto') }}</a>
        </nav>
    @else
        <nav></nav>
    @endif
    <span class="who">
        @include('partials.language')
        <button type="button" class="theme small" id="theme-toggle" title="{{ __('Hell/Dunkel umschalten') }}" aria-label="{{ __('Hell/Dunkel umschalten') }}">◐</button>
        @if($u)
            <span class="name">{{ $u->displayName() }}</span>
            @include('partials.session-clock')
            <form method="post" action="{{ route('logout') }}" class="inline-form">@csrf<button class="small">🚪 {{ __('Abmelden') }}</button></form>
        @endif
    </span>
</header>
@if($u && \App\Support\SummerHoliday::isSoon())
    @php($summerDate = \App\Support\SummerHoliday::date()->format('d.m.Y'))
    <div class="summer-banner">☀️
        @if($u->isPupil())
            {{ __('Am :datum beginnt die Sommerpause: Dann werden alle Bücher gelöscht. Nimm deine Bücher vorher mit nach Hause — beim Buch auf „⋯“ und „Als Datei mitnehmen“ tippen.', ['datum' => $summerDate]) }}
        @else
            {{ __('Sommerferienmodus: Am :datum werden alle Schülerkonten mit ihren Büchern endgültig gelöscht.', ['datum' => $summerDate]) }}
        @endif
    </div>
@endif
<main>
    @if(session('ok'))<div class="flash ok">{{ session('ok') }}</div>@endif
    @if(session('notice'))<div class="flash info">{{ session('notice') }}</div>@endif
    @if($errors->any())
        <div class="flash err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif
    @yield('content')
</main>
@if($u && $u->showsFox())
    @include('partials.fox')
@elseif(! $u && request()->routeIs('login') && \App\Models\Setting::get('fox_enabled', '1') === '1')
    {{-- Anmeldeseite: der Fuchs wirbt für freie Software / Sign-in page: the fox helper promotes free software --}}
    @include('partials.fox', ['foxMode' => 'login'])
@endif
<footer class="bottom">
    @if(\App\Support\Branding::footerText())<div>{{ \App\Support\Branding::footerText() }}</div>@endif
    <div>
        BilDaBooky {{ \App\Support\License::edition() }} {{ config('version.number') }}
        · @if(config('bildabooky.source_url'))<a href="{{ config('bildabooky.source_url') }}" rel="noopener">{{ __('freie Software (AGPL-3.0)') }}</a>@else{{ __('freie Software (AGPL-3.0)') }}@endif
        · {{ __('alle Daten bleiben auf dem Schulserver') }}
        @if($u) · <a href="{{ route('help') }}">❓ {{ __('Hilfe') }}</a>@endif
        @foreach(\App\Support\LegalPages::links() as $r)
            · <a href="{{ $r['url'] }}" @if($r['external']) target="_blank" rel="noopener" @endif>{{ $r['title'] }}</a>
        @endforeach
    </div>
    <div class="maker">
        <a href="https://www.mai-digital.de" target="_blank" rel="noopener">
            <img src="{{ asset('img/mai-digital.svg') }}" alt="" width="22" height="22">
            <span>Steffen Mai · www.mai-digital.de</span>
        </a>
    </div>
</footer>
@endif
<script>
    // Aufklappmenüs schließen, wenn man daneben tippt
    // Close drop-down menus when tapping outside them
    // (nur Menüs, deren .menu direkt darin liegt — nicht ganze Bereiche mit Buchkarten)
    // (only menus whose .menu sits directly inside — not whole sections with book cards)
    document.addEventListener('click', (e) => document.querySelectorAll('details[open]').forEach((d) => {
        if (d.querySelector(':scope > .menu') && !d.contains(e.target)) d.open = false;
    }));
</script>
<script>
    (function () {
        var button = document.getElementById('theme-toggle');
        if (button) button.addEventListener('click', function () {
            var next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = next;
            try { localStorage.setItem('theme', next); } catch (e) {}
        });
    })();
</script>
@stack('scripts')
</body>
</html>

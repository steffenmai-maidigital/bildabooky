<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    @hasSection('reload')<meta http-equiv="refresh" content="@yield('reload')">@endif
    <title>@yield('title') · BilDaBooky</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ config('version.build') }}">
    <meta name="symbol-base" content="{{ rtrim(asset(''), '/') }}">
    <script src="{{ asset('js/symbols.js') }}?v={{ config('version.build') }}" defer></script>
    @php
        // Fehlerseiten dürfen selbst nie scheitern (z. B. wenn die Datenbank fehlt)
        // Error pages must never fail themselves (e.g. when the database is missing)
        try { $brandCss = \App\Support\Branding::css(); $logo = \App\Support\Branding::logoUrl(); $school = \App\Support\Branding::school(); }
        catch (\Throwable $e) { $brandCss = ''; $logo = asset('img/logo.svg'); $school = 'BilDaBooky'; }
        $code = (int) trim($__env->yieldContent('code'));
        $error = $exception ?? null;
        $reason = \App\Support\ErrorPage::reason($error);
        $signedIn = \App\Support\ErrorPage::signedIn();
        // Anmelden statt Bibliothek, wo die Anmeldung fehlt oder abgelaufen ist
        // Sign in instead of library where the sign-in is missing or has expired
        $toLogin = ! $signedIn && in_array($code, [401, 403, 419], true);
    @endphp
    <style>{!! $brandCss !!}</style>
    <script>
        (function () {
            var t = null; try { t = localStorage.getItem('theme'); } catch (e) {}
            document.documentElement.dataset.theme = t || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        })();
    </script>
</head>
<body>
<main>
    <div class="panel error-page">
        <img class="error-logo" src="{{ $logo }}" alt="" width="72" height="72">
        <div class="error-symbol" aria-hidden="true">@yield('symbol')</div>
        <p class="error-code">{{ __('Fehler') }} {{ $code }}</p>
        <h1>@yield('title')</h1>
        <p class="error-text">@yield('text')</p>

        @if($reason)
            <p class="error-reason">{{ $reason }}</p>
        @endif

        @hasSection('tips')
            <div class="error-tips">
                <h2>{{ __('Das kannst du tun') }}</h2>
                <ul>@yield('tips')</ul>
            </div>
        @endif

        <div class="actions">
            @if($toLogin && Route::has('login'))
                <a class="btn primary" href="{{ route('login') }}">🔑 {{ __('Anmelden') }}</a>
            @else
                <a class="btn primary" href="{{ url('/') }}">📚 {{ __('Zur Bibliothek') }}</a>
            @endif
            <a class="btn secondary" href="javascript:history.back()">‹ {{ __('Zurück') }}</a>
            @if($signedIn && Route::has('help') && $code < 500)
                <a class="btn secondary" href="{{ route('help') }}">📘 {{ __('Hilfe') }}</a>
            @endif
        </div>

        @if($code >= 500 && \App\Support\ErrorPage::isLogged($error))
            {{-- Fehlernummer: steht gleich im Protokoll (Hilfe → Letzte Fehler)

                 Error reference: the same one is in the log (help → recent errors) --}}
            <p class="error-reference">
                {{ __('Fehlernummer') }} <code>{{ \App\Support\ErrorPage::reference() }}</code>
                · {{ now()->format('d.m.Y H:i') }}
            </p>
        @endif

        <p class="hint">{{ $school }} · BilDaBooky</p>
    </div>
</main>
</body>
</html>

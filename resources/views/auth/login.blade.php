@extends('layouts.app')
@section('title', __('Anmelden'))
@section('content')
    <div class="panel login-box">
        <div class="logo">
            <img src="{{ \App\Support\Branding::logoUrl() }}" alt="">
            <b>{{ \App\Support\Branding::school() }}</b>
            <span class="hint">BilDaBooky</span>
        </div>
        @if(session('no_session'))
            <div class="locked-notice" role="alert">
                <span aria-hidden="true">🔒</span>
                <div><b>{{ __('Für deine Klasse ist keine Sitzung freigegeben.') }}</b>
                    {{ __('Frag deine Lehrkraft — sie schaltet die Anmeldung frei, wenn es losgeht.') }}</div>
            </div>
        @endif
        @if($directory ?? false)
            <p class="hint" style="margin-top:0">{{ __('Kinder melden sich mit ihrem BilDaBooky-Namen an, Lehrkräfte und Admins auch mit ihrem Schulkonto.') }}</p>
        @endif
        <form method="post" action="{{ route('login') }}">
            @csrf
            <label for="username">{{ __('Benutzername') }}</label>
            <input type="text" id="username" name="username" value="{{ old('username') }}" autofocus required
                   autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false">
            <label for="password">{{ __('Passwort') }}</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
            <div class="actions"><button class="primary" style="width:100%">{{ __('Los geht\'s') }}</button></div>
        </form>
        @if(config('bildabooky.demo'))
            <div class="flash info" style="margin-top:1rem">
                <b>{{ __('Demo') }}:</b> {{ __('Lehrkräfte') }} <code>sommer</code> / <code>sommer123</code> {{ __('oder') }} <code>winter</code> / <code>winter123</code>,
                {{ __('Schüler') }} <code>mia</code> / <code>mia</code> {{ __('oder') }} <code>ben</code> / <code>ben</code>
            </div>
        @endif
        @if($legalLinks = \App\Support\LegalPages::links())
            <p class="login-legal">
                @foreach($legalLinks as $type => $r)
                    <a href="{{ $r['url'] }}" @if($r['external']) target="_blank" rel="noopener" @endif>{{ $type === 'imprint' ? '⚖️' : '🔒' }} {{ $r['title'] }}</a>
                @endforeach
            </p>
        @endif
    </div>
@endsection

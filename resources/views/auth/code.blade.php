@extends('layouts.app')
@section('title', __('Bestätigungscode'))
@section('content')
    <div class="panel login-box">
        <div class="logo"><img src="{{ \App\Support\Branding::logoUrl() }}" alt=""><b>{{ __('Bestätigung') }}</b></div>
        <p>{{ __('Gib den 6-stelligen Code aus deiner Authenticator-App ein. Ohne Handy geht auch einer deiner Wiederherstellungscodes.') }}</p>
        <form method="post" action="{{ route('login.code') }}">
            @csrf
            <label for="code">{{ __('Code') }}</label>
            <input type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" autofocus required maxlength="20"
                   style="font-size:1.5rem;letter-spacing:.3em;text-align:center">
            <div class="actions"><button class="primary" style="width:100%">✅ {{ __('Bestätigen') }}</button></div>
        </form>
        <p class="hint" style="text-align:center"><a href="{{ route('login') }}">{{ __('Abbrechen') }}</a></p>
    </div>
@endsection

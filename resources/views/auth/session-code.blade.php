@extends('layouts.app')
@section('title', __('Sitzungscode'))
@section('content')
    <div class="panel login-box">
        <div class="logo"><img src="{{ \App\Support\Branding::logoUrl() }}" alt=""><b>🔢 {{ __('Sitzungscode') }}</b></div>
        <p>{{ __('Deine Lehrkraft hat dir einen Code mit 6 Zahlen gegeben. Tippe ihn hier ein.') }}</p>
        <form method="post" action="{{ route('login.session-code') }}">
            @csrf
            <label for="code">{{ __('Code') }}</label>
            <input type="text" id="code" name="code" inputmode="numeric" pattern="[0-9 ]*" autocomplete="one-time-code" autofocus required maxlength="9"
                   style="font-size:1.8rem;letter-spacing:.35em;text-align:center">
            <div class="actions"><button class="primary" style="width:100%">✅ {{ __('Los geht\'s') }}</button></div>
        </form>
        <p class="hint" style="text-align:center"><a href="{{ route('login') }}">{{ __('Abbrechen') }}</a></p>
    </div>
@endsection

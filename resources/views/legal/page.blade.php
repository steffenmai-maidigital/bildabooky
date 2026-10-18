@extends('layouts.app')
@section('title', $title)
@section('content')
    <div class="panel legal-page">
        <h1 style="margin-top:0">{{ $title === __('Impressum') ? '⚖️' : '🔒' }} {{ $title }}</h1>
        {!! $html !!}
    </div>
    <p><a href="{{ auth()->check() ? route('home') : route('login') }}">‹ {{ auth()->check() ? __('Zur Bibliothek') : __('Zur Anmeldung') }}</a></p>
@endsection

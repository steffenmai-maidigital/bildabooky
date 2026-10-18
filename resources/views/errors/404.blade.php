@extends('errors.layout')
@section('symbol', '🔍')
@section('code', '404')
@section('title', __('Seite nicht gefunden'))
@section('text', __('Diese Seite gibt es nicht (mehr). Vielleicht wurde das Buch gelöscht oder der Link ist falsch.'))
@section('tips')
    <li>{{ __('Prüfe die Adresse auf Tippfehler.') }}</li>
    <li>{{ __('Geh über die Bibliothek zu deinem Buch.') }}</li>
    <li>{{ __('Ein altes Lesezeichen? Speichere es danach neu.') }}</li>
@endsection

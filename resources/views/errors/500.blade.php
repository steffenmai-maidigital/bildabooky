@extends('errors.layout')
@section('symbol', '🛠')
@section('code', '500')
@section('title', __('Da ist etwas schiefgegangen'))
@section('text', __('Das ist nicht deine Schuld. Das Letzte, was du gemacht hast, wurde vielleicht nicht gespeichert.'))
@section('tips')
    <li>{{ __('Versuche es gleich noch einmal.') }}</li>
    <li>{{ __('Passiert es wieder, sag der Schul-IT die Fehlernummer unten. Damit findet sie den Eintrag unter Hilfe → Letzte Fehler.') }}</li>
@endsection

@extends('errors.layout')
@section('symbol', '🔑')
@section('code', '401')
@section('title', __('Bitte melde dich an'))
@section('text', __('Diese Seite siehst du erst nach dem Anmelden.'))
@section('tips')
    <li>{{ __('Melde dich mit deinem Benutzernamen und Passwort an – oder mit dem Sitzungscode deiner Klasse.') }}</li>
    <li>{{ __('Du hast noch kein Konto? Deine Lehrkraft oder die Schul-IT legt eines an.') }}</li>
@endsection

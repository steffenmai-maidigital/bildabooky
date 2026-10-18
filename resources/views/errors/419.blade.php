@extends('errors.layout')
@section('symbol', '⏰')
@section('code', '419')
@section('title', __('Die Sitzung ist abgelaufen'))
@section('text', __('Du warst eine Weile nicht aktiv. Darum wurde das gerade nicht gespeichert.'))
@section('tips')
    <li>{{ __('Geh zurück, lade die Seite neu und versuche es noch einmal.') }}</li>
    <li>{{ __('Was im Buch schon gespeichert war, ist nicht weg.') }}</li>
@endsection

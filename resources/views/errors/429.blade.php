@extends('errors.layout')
@section('symbol', '✋')
@section('code', '429')
@section('title', __('Einen Moment bitte'))
@section('text', __('Das waren zu viele Versuche auf einmal.'))
@section('tips')
    @php($seconds = \App\Support\ErrorPage::retryAfter($exception ?? null))
    <li>{{ $seconds ? __('Versuche es in etwa :sek Sekunden noch einmal.', ['sek' => $seconds]) : __('Warte eine Minute und versuche es dann noch einmal.') }}</li>
    <li>{{ __('Nach mehreren falschen Anmeldungen wartet BilDaBooky kurz – das schützt vor dem Erraten von Passwörtern.') }}</li>
@endsection

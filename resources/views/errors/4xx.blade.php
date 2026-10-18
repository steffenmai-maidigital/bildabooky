@extends('errors.layout')
@section('symbol', '⚠️')
@section('code', (string) (isset($exception) && method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 400))
@section('title', __('Das hat nicht geklappt'))
@section('text', __('Das ging gerade nicht.'))
@section('tips')
    <li>{{ __('Geh zurück und versuche es noch einmal.') }}</li>
    <li>{{ __('Klappt es wieder nicht, frag deine Lehrkraft.') }}</li>
@endsection

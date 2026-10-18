@extends('errors.layout')
@section('symbol', '🔒')
@section('code', '403')
@section('title', __('Hier darfst du nicht hinein'))
@section('text', __('Dieses Buch oder diese Seite ist nicht für dich freigegeben.'))
@section('tips')
    <li>{{ __('Prüfe, ob du mit deinem eigenen Konto angemeldet bist.') }}</li>
    <li>{{ __('Bücher anderer Kinder siehst du erst, wenn sie geteilt sind.') }}</li>
    <li>{{ __('Glaubst du, das ist ein Fehler? Frag deine Lehrkraft.') }}</li>
@endsection

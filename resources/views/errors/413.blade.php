@extends('errors.layout')
@section('symbol', '📦')
@section('code', '413')
@section('title', __('Die Datei ist zu groß'))
@section('text', __('Das Bild, der Ton oder die Datei ist größer, als der Server annimmt (höchstens :max).', ['max' => \App\Support\ErrorPage::uploadLimit()]))
@section('tips')
    <li>{{ __('Nimm ein kleineres Bild oder eine kürzere Aufnahme.') }}</li>
    <li>{{ __('Fotos vom Tablet vorher verkleinern hilft meist schon.') }}</li>
    <li>{{ __('Die Schul-IT kann die Grenze erhöhen (PHP: upload_max_filesize und post_max_size).') }}</li>
@endsection

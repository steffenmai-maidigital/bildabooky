@extends('layouts.app')
@section('title', __('Anmeldekarten'))
@section('body-class', 'print-only-cards')
@section('content')
    <div class="no-print lib-head">
        <h1>🪪 {{ __('Anmeldekarten') }}</h1>
        <div class="actions" style="margin:0">
            <button class="primary" onclick="print()">🖨 {{ __('Drucken') }}</button>
            <a class="btn" href="{{ url()->previous() }}">{{ __('Zurück') }}</a>
        </div>
    </div>
    @if(empty($credentials))
        <div class="panel">{{ __('Für diese Auswahl sind keine Anmeldekarten hinterlegt. Eine Karte entsteht, wenn beim Anlegen oder beim Zurücksetzen des Passworts „Anmeldekarte hinterlegen“ angehakt ist.') }}</div>
    @else
        @include('partials.credentials')
    @endif
@endsection

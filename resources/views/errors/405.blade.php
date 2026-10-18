@extends('errors.layout')
@section('symbol', '🧭')
@section('code', '405')
@section('title', __('Diese Adresse geht so nicht'))
@section('text', __('Die Seite lässt sich auf diesem Weg nicht öffnen – meist nach dem Neuladen eines abgeschickten Formulars.'))
@section('tips')
    <li>{{ __('Geh zurück und öffne die Seite über die Bibliothek neu.') }}</li>
@endsection

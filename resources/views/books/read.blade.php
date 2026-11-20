@extends('layouts.app')
@section('title', $book->title)
@section('body-class', 'reader-body')
@push('head')
    @include('partials.reader-style')
@endpush
@section('fullscreen')
    @include('partials.reader', ['standalone' => false])
    @if($canReturn)@include('partials.return-dialog')@endif
    @if(auth()->user()->showsCompanion())
        @include('partials.companion', ['companionBook' => $book, 'companionMode' => 'reading'])
    @endif
@endsection
@push('scripts')
    <script src="{{ asset('js/book.js') }}?v={{ config('version.build') }}"></script>
    <script>
        BDB.base = @json(rtrim(url('/'), '/'));
        {{-- Werte erst in eine Variable: @json teilt seinen Ausdruck an Kommas und verlöre sonst die Schutz-Flags (JSON_HEX_TAG …)
             values into a variable first: @json splits its expression at commas and would otherwise lose the safety flags (JSON_HEX_TAG …) --}}
        @php($bookJs = ['format' => $book->format, 'pages' => $book->pages->map->toEditorArray()->values()])
        window.BOOK = @json($bookJs);
    </script>
    <script src="{{ asset('js/reader.js') }}?v={{ config('version.build') }}"></script>
@endpush

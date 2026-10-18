@extends('layouts.app')
@section('title', $book->title)
@section('body-class', 'reader-body')
@push('head')
    @include('partials.reader-style')
@endpush
@section('fullscreen')
    @include('partials.reader', ['standalone' => false])
    @if($canReturn)@include('partials.return-dialog')@endif
    @if(auth()->user()->showsFox())
        @include('partials.fox', ['foxBook' => $book, 'foxMode' => 'reading'])
    @endif
@endsection
@push('scripts')
    <script src="{{ asset('js/book.js') }}?v={{ config('version.build') }}"></script>
    <script>
        BDB.base = @json(rtrim(url('/'), '/'));
        window.BOOK = @json(['format' => $book->format, 'pages' => $book->pages->map->toEditorArray()->values()]);
    </script>
    <script src="{{ asset('js/reader.js') }}?v={{ config('version.build') }}"></script>
@endpush

<!doctype html>
{{-- Das Buch als eine einzige Datei: läuft offline, ohne Anmeldung und ohne Server

     The book as one single file: works offline, without sign-in and without a server --}}
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="generator" content="BilDaBooky {{ \App\Support\License::edition() }} {{ config('version.number') }}">
    <title>{{ $book->title }}</title>
    <style>{!! $css !!}</style>
    <style>{!! \App\Support\Branding::css() !!}</style>
    @include('partials.reader-style')
</head>
<body class="reader-body">
@include('partials.reader', ['standalone' => true, 'canEdit' => false])
<script>
    window.BDB_TEXTS = @json((object) \App\Support\Lang::forJs());
    window.BDB = { media: @json($media, JSON_UNESCAPED_SLASHES), emoji: @json($emoji, JSON_UNESCAPED_SLASHES) };
    window.BOOK = @json(['format' => $book->format, 'pages' => $book->pages->map->toEditorArray()->values(), 'standalone' => true]);
</script>
<script>{!! str_replace('</script', '<\/script', $js) !!}</script>
</body>
</html>

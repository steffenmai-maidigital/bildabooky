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
    window.BDB = { media: @json($media, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP), emoji: @json($emoji, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) };
    {{-- Werte erst in eine Variable: @json teilt seinen Ausdruck an Kommas und verlöre sonst die Schutz-Flags (JSON_HEX_TAG …)
         values into a variable first: @json splits its expression at commas and would otherwise lose the safety flags (JSON_HEX_TAG …) --}}
    @php($bookJs = ['format' => $book->format, 'pages' => $book->pages->map->toEditorArray()->values(), 'standalone' => true])
    window.BOOK = @json($bookJs);
</script>
<script>{!! str_replace('</script', '<\/script', $js) !!}</script>
</body>
</html>

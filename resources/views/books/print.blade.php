@php($size = ['portrait' => ['210mm', '297mm', 'A4 portrait'], 'landscape' => ['297mm', '210mm', 'A4 landscape'], 'square' => ['210mm', '210mm', '210mm 210mm']][$book->format])
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $book->title }} – {{ __('Drucken') }}</title>
    <link rel="stylesheet" href="{{ asset('css/book.css') }}?v={{ config('version.build') }}">
    <style>{!! \App\Support\Branding::css() !!}</style>
    <style>
        @page { size: {{ $size[2] }}; margin: 0; }
        body { margin: 0; background: #e9e4dc; font-family: system-ui, sans-serif; }
        .toolbar { position: sticky; top: 0; background: #fff; padding: .7rem 1rem; display: flex; gap: .8rem; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,.1); z-index: 2; }
        .toolbar button { font: inherit; font-weight: 700; border: 0; border-radius: 999px; padding: .6rem 1.2rem; background: var(--accent); color: var(--accent-ink); cursor: pointer; }
        .pages { display: flex; flex-direction: column; align-items: center; gap: 1.5rem; padding: 1.5rem; }
        .print-page { width: {{ $size[0] }}; height: {{ $size[1] }}; background: #fff; box-shadow: 0 4px 18px rgba(0,0,0,.15); overflow: hidden; }
        .print-page .bdb-frame { width: 100%; }
        @media print {
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            body { background: #fff; }
            .toolbar { display: none; }
            .pages { padding: 0; gap: 0; display: block; }
            .print-page { box-shadow: none; break-after: page; }
            .bdb-audio { display: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="print()">🖨 {{ __('Drucken / als PDF speichern') }}</button>
        <span>{{ $book->title }} · {{ __(':n Seiten', ['n' => $book->pages->count()]) }} · {{ __('Tonaufnahmen werden nicht gedruckt') }}</span>
    </div>
    <div class="pages" id="pages">
        @foreach($book->pages as $p)<div class="print-page"><div></div></div>@endforeach
    </div>
    <script src="{{ asset('js/book.js') }}?v={{ config('version.build') }}"></script>
    <script>
        BDB.base = @json(rtrim(url('/'), '/'));
        const pages = @json($book->pages->map->toEditorArray()->values());
        document.querySelectorAll('.print-page > div').forEach((r, i) => BDB.page(r, pages[i], @json($book->format)));
    </script>
</body>
</html>

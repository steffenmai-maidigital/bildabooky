@extends('layouts.app')
@section('title', $book->title)
@section('body-class', 'editor-body')
@push('head')
    <link rel="stylesheet" href="{{ asset('css/editor.css') }}?v={{ config('version.build') }}">
@endpush
@section('fullscreen')
<div class="ed" id="ed">
    <header class="ed-header">
        <a class="ed-button" href="{{ route('home') }}" title="{{ __('Zur Bibliothek') }}">‹ <span class="ed-wide-only">{{ __('Bibliothek') }}</span></a>
        <input class="ed-title" id="ed-title" value="{{ $book->title }}" maxlength="120" aria-label="{{ __('Buchtitel') }}" @unless($rights['manage']) readonly @endunless>
        @if($rights['collaborative'])<span class="ed-others" id="ed-others" title="{{ __('Gerade dabei') }}"></span>@endif
        <span class="ed-status" id="ed-status" aria-live="polite"></span>
        @if($rights['log'])<a class="ed-button" href="{{ route('books.log', $book) }}" title="{{ __('Änderungsprotokoll') }}" aria-label="{{ __('Änderungsprotokoll') }}" target="_blank">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l3 2"/></svg>
            <span class="ed-wide-only">{{ __('Protokoll') }}</span></a>@endif
        <button class="ed-button ed-explain" id="ed-explain" type="button" title="{{ __('Erklär-Modus') }}" aria-pressed="false">❓</button>
        <button class="ed-button" id="ed-undo" title="{{ __('Rückgängig (Strg+Z)') }}" disabled>↶</button>
        <button class="ed-button" id="ed-settings" title="{{ __('Buch-Einstellungen') }}">⚙️</button>
        @if($rights['canSubmit'])
            <button class="ed-button ed-submit-button" id="ed-submit" type="button" data-submitted="{{ $book->submitted_at?->format('d.m.Y H:i') }}"><span class="ed-submit-symbol">✅</span> <span class="ed-wide-only ed-submit-word">{{ __('Abgeben') }}</span></button>
        @endif
        @include('partials.session-clock', ['class' => 'ed-button'])
        <a class="ed-button ed-read" id="ed-read" href="{{ route('books.read', $book) }}" title="{{ __('Lesen') }}">📖 <span class="ed-wide-only">{{ __('Lesen') }}</span></a>
    </header>

    @if($rights['canSubmit'] && $book->isReturned())
        <div class="ed-returned" id="ed-returned" role="status" data-key="{{ $book->id }}-{{ $book->returned_at->timestamp }}">
            <span class="ed-returned-symbol">↩️</span>
            <div><b>{{ __(':name hat dein Buch zurückgegeben.', ['name' => $book->returnedBy?->displayName() ?? __('Deine Lehrkraft')]) }}</b>
                @if($book->return_note)<span class="ed-returned-note">„{{ $book->return_note }}“</span>@endif
                <small>{{ __('Wenn du fertig bist, tippe oben wieder auf „Abgeben“.') }}</small></div>
            <button type="button" class="ed-returned-close" aria-label="{{ __('Schließen') }}" title="{{ __('Schließen') }}">✕</button>
        </div>
    @endif
    <div class="ed-middle">
        <nav class="ed-tools" aria-label="{{ __('Hinzufügen') }}">
            <button data-tool="text" title="{{ __('Text') }}"><b>Aa</b><span>{{ __('Text') }}</span></button>
            <button data-tool="photo" title="{{ __('Foto') }}"><b data-emoji="🖼️">🖼️</b><span>{{ __('Foto') }}</span></button>
            <button data-tool="camera" title="{{ __('Kamera') }}"><b data-emoji="📷">📷</b><span>{{ __('Kamera') }}</span></button>
            <button data-tool="paint" title="{{ __('Malen') }}"><b data-emoji="✏️">✏️</b><span>{{ __('Malen') }}</span></button>
            <button data-tool="audio" title="{{ __('Ton') }}"><b data-emoji="🎤">🎤</b><span>{{ __('Ton') }}</span></button>
            <button data-tool="sticker" title="{{ __('Sticker') }}"><b data-emoji="⭐">⭐</b><span>{{ __('Sticker') }}</span></button>
            <button data-tool="shape" title="{{ __('Form') }}"><b data-emoji="🟡">🟡</b><span>{{ __('Form') }}</span></button>
            <button data-tool="background" title="{{ __('Seite') }}"><b data-emoji="🎨">🎨</b><span>{{ __('Seite') }}</span></button>
        </nav>

        <div class="ed-stage" id="ed-stage">
            <div class="ed-page-frame no-symbols" id="ed-page-frame"></div>
        </div>

        <aside class="ed-properties" id="ed-properties" hidden></aside>

        @if(auth()->user()->showsFox())
            {{-- Fuchs unten in der rechten Spalte (Handy: über den Werkzeugen)

                 Fox helper at the bottom of the right column (phone: above the tools) --}}
            @include('partials.fox', ['foxBook' => $book])
        @endif
    </div>

    <footer class="ed-footer">
        <div class="ed-page-bar" id="ed-page-bar"></div>
    </footer>

    <input type="file" id="ed-file-photo" accept="image/*" hidden>
    <input type="file" id="ed-file-camera" accept="image/*" capture="environment" hidden>
</div>

<dialog id="ed-dialog"><div id="ed-dialog-content"></div></dialog>

<dialog id="ed-book-dialog">
    <h2 style="margin-top:0">⚙️ {{ __('Buch-Einstellungen') }}</h2>
    @if($book->isClassBook())
        <p class="flash info">👥 {{ __('Klassenbuch: Die ganze Klasse schreibt hier zusammen. Änderungen der anderen erscheinen nach ein paar Sekunden.') }}
            @unless($rights['reorderPages']) {{ __('Seiten löschen und umsortieren kann nur die Lehrkraft.') }} @endunless</p>
    @elseif($book->isTemplate())
        <p class="flash info">📋 {{ __('Vorlage: Jedes Kind der Klasse legt daraus seine eigene Version an.') }}
            <a href="{{ route('books.versions', $book) }}">🧒 {{ __('Versionen der Kinder') }}</a></p>
    @elseif($book->isVersion())
        <p class="flash info">📋 {{ __('Deine Version der Vorlage „:titel“. Deine Lehrkraft kann sie sehen.', ['titel' => $book->template?->title]) }}</p>
    @endif

    @if($rights['manage'])
        <label>{{ __('Format') }}</label>
        <div class="format-choice">
            @foreach(['portrait' => [34, 48, __('Hoch')], 'square' => [44, 44, __('Quadrat')], 'landscape' => [52, 37, __('Quer')]] as $k => [$w, $h, $t])
                <label><input type="radio" name="ed-format" value="{{ $k }}" @checked($book->format === $k)><i style="width:{{ $w }}px;height:{{ $h }}px"></i>{{ $t }}</label>
            @endforeach
        </div>
    @endif

    @if($subjects->isNotEmpty())
        <label for="ed-subject">{{ __('Fach') }}</label>
        <select id="ed-subject">
            <option value="">🗂️ {{ __('Ohne Fach') }}</option>
            @foreach($subjects as $f)<option value="{{ $f->id }}" @selected($book->subject_id === $f->id)>{{ $f->symbol }} {{ $f->name }}</option>@endforeach
        </select>
    @endif

    @if($rights['share'])
        <label>{{ __('Teilen') }}</label>
        <div class="share" id="ed-share" data-sharing="{{ $book->sharing }}">
            <button type="button" class="share-button" data-level="teacher">🍎 {{ __('Mit der Lehrkraft teilen') }}</button>
            <button type="button" class="share-button" data-level="class">👥 {{ __('Mit der Klasse teilen') }}</button>
        </div>
        <p class="hint">
            @if($rights['othersBook']){{ __('Bestimmt, wer außer :name das Buch sieht. Als Lehrkraft siehst du es immer.', ['name' => $book->owner->first_name]) }}
            @else{{ __('Nochmal tippen hebt das Teilen auf. Ohne Teilen sehen nur du und deine Lehrkraft dein Buch.') }}@endif
        </p>
    @endif

    @if($rights['manage'] && ! auth()->user()->isPupil() && $classes->isNotEmpty() && ! $book->owner->isPupil())
        <label for="ed-class">{{ __('Klasse') }}</label>
        <select id="ed-class">
            @if($book->kind === 'own')<option value="">—</option>@endif
            @foreach($classes as $c)<option value="{{ $c->id }}" @selected($book->school_class_id == $c->id)>{{ $c->name }}</option>@endforeach
        </select>
    @endif

    @unless($book->owner->is(auth()->user()) || $book->isClassBook())
        <p class="hint">{{ __('Dieses Buch gehört :name.', ['name' => $book->owner->name]) }}</p>
    @endunless
    <div class="actions"><button class="primary" onclick="this.closest('dialog').close()">{{ __('Fertig') }}</button></div>
</dialog>
@endsection
@push('scripts')
    <script src="{{ asset('js/book.js') }}?v={{ config('version.build') }}"></script>
    <script>
        BDB.base = @json(rtrim(url('/'), '/'));
        window.EDITOR = {
            book: @json($book->toEditorArray()),
            urls: {
                book: @json(route('books.update', $book)),
                pages: @json(route('pages.store', $book)),
                order: @json(route('pages.order', $book)),
                media: @json(route('media.store', $book)),
                read: @json(route('books.read', $book)),
                state: @json(route('pages.state', $book)),
                submit: @json(route('books.submit', $book)),
            },
            rights: @json($rights),
            owner: @json($owner),
            csrf: @json(csrf_token()),
        };
    </script>
    <script src="{{ asset('js/editor.js') }}?v={{ config('version.build') }}"></script>
@endpush

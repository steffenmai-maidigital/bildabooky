<div class="reader">
    <div class="reader-header">
        @unless($standalone)<a href="{{ route('home') }}">‹ {{ __('Bibliothek') }}</a>@else<span></span>@endunless
        <b>{{ $book->title }} <span style="opacity:.6;font-weight:500">· {{ $book->owner->first_name }}</span></b>
        <span style="display:flex;gap:.4rem">
            @unless($standalone)@include('partials.session-clock')@endunless
            <button id="reader-fullscreen" title="{{ __('Vollbild') }}">⛶</button>
            @if(! $standalone && ($canSubmit ?? false))
                @php($at = $book->submitted_at?->format('d.m.Y H:i'))
                <form method="post" action="{{ route('books.submit', $book) }}" class="reader-submit"
                      onsubmit="return confirm(@js($at ? __('Abgabe zurücknehmen? Deine Lehrkraft sieht dann wieder, dass du noch arbeitest.') : __('Buch jetzt abgeben? Deine Lehrkraft sieht dann, dass du fertig bist. Du kannst danach trotzdem weiterarbeiten.')))">
                    @csrf
                    <input type="hidden" name="done" value="{{ $at ? 0 : 1 }}">
                    <button class="{{ $at ? 'submitted' : '' }}" title="{{ $at ? __('Abgegeben am :datum. Antippen, um die Abgabe zurückzunehmen.', ['datum' => $at]) : __('Abgeben') }}">{{ $at ? '📬 ' . __('Abgegeben') : '✅ ' . __('Abgeben') }}</button>
                </form>
            @endif
            @if(! $standalone && ($canReturn ?? false))
                <button type="button" class="reader-return" data-return="{{ route('books.return', $book) }}" data-name="{{ $book->owner->first_name }}">↩️ {{ __('Zurückgeben') }}</button>
            @endif
            @if($canEdit)<a href="{{ route('books.edit', $book) }}">✏️ {{ __('Bearbeiten') }}</a>@endif
        </span>
    </div>
    @if(! $standalone && ($canSubmit ?? false) && $book->isReturned())
        <div class="reader-returned" role="status">
            ↩️ <b>{{ __(':name hat dein Buch zurückgegeben.', ['name' => $book->returnedBy?->displayName() ?? __('Deine Lehrkraft')]) }}</b>
            @if($book->return_note)<span>„{{ $book->return_note }}“</span>@endif
        </div>
    @endif
    <div class="reader-stage" id="reader-stage"></div>
    <div class="reader-footer">
        <button class="page-turn" id="reader-prev" aria-label="{{ __('Zurück') }}">‹</button>
        <span id="reader-counter"></span>
        <button class="page-turn" id="reader-next" aria-label="{{ __('Weiter') }}">›</button>
    </div>
</div>

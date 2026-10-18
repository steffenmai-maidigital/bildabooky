{{-- Lehrkraft: Bücher der Kinder einer Klasse, je Kind gruppiert (Aufgaben, geteilt, nicht geteilt)

     Teacher: the pupils' books of a class, grouped per pupil (assignments, shared, not shared) --}}
@if(empty($s['pupils']) && empty($s['without']))
    <div class="empty">{{ $s['empty'] }}</div>
@else
    <div class="pupils-tools" data-pupils-tools>
        <div class="filter-chips" role="group" aria-label="{{ __('Filter') }}">
            <button type="button" class="chip on" data-filter="all">{{ __('Alle') }}</button>
            <button type="button" class="chip" data-filter="assignment">📝 {{ __('Aufgaben') }}</button>
            <button type="button" class="chip" data-filter="submitted">✅ {{ __('Abgegeben') }}</button>
            <button type="button" class="chip" data-filter="shared">🤝 {{ __('Geteilt') }}</button>
            <button type="button" class="chip" data-filter="private">🔒 {{ __('Nicht geteilt') }}</button>
        </div>
        <span class="pupils-toggle">
            <button type="button" class="small" data-all="open">{{ __('Alle aufklappen') }}</button>
            <button type="button" class="small" data-all="close">{{ __('Alle einklappen') }}</button>
        </span>
    </div>
    <div class="pupils-list">
        @foreach($s['pupils'] as $z)
            @php($pupil = $z['pupil'])
            <details class="library-section pupil-section" data-section="{{ $s['key'] }}-{{ $pupil->id }}" open>
                <summary>
                    <span class="pupil-name"><b>{{ $pupil->last_name }}</b>{{ $pupil->last_name ? ',' : '' }} {{ $pupil->first_name }}</span>
                    <span class="pupil-counts">
                        <span class="badge">{{ trans_choice(':n Buch|:n Bücher', $z['count'], ['n' => $z['count']]) }}</span>
                        @if($z['submitted'])<span class="badge ok">✓ {{ __(':n abgegeben', ['n' => $z['submitted']]) }}</span>@endif
                    </span>
                </summary>
                @foreach($z['groups'] as $g)
                    <div class="section-group" data-group="{{ $g['key'] }}">
                        <h4 class="group-title">{{ $g['symbol'] }} {{ $g['title'] }} <span class="count">{{ $g['books']->count() }}</span></h4>
                        <div class="shelf">
                            @foreach($g['books'] as $book)
                                <div class="card-wrap" data-kind="{{ $g['key'] }}" data-status="{{ $book->submitted_at ? 'submitted' : ($book->isReturned() ? 'returned' : 'in-progress') }}">
                                    @include('partials.book-card', ['book' => $book, 'showAuthor' => false, 'myVersions' => collect()])
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </details>
        @endforeach
    </div>
    <p class="pupils-empty-filter hint" hidden>{{ __('Keine Bücher zu diesem Filter.') }}</p>
    @if(! empty($s['without']))
        <p class="pupils-without hint">{{ __('Noch ohne Bücher:') }}
            {{ collect($s['without'])->map(fn ($p) => trim($p->first_name . ' ' . $p->last_name))->join(', ') }}</p>
    @endif
@endif

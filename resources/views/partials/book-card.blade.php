@php
    $me = auth()->user();
    $canEdit = $book->editableBy($me);
    $manage = $book->manageableBy($me);
    // Vorlage aus Sicht eines Kindes: eigene Version starten bzw. weitermachen
    // Template from a pupil's view: start or continue their own version
    $pupilTemplate = $book->isTemplate() && $me->isPupil();
    $mine = $pupilTemplate ? ($myVersions[$book->id] ?? null) : null;
    $shareable = $manage && $book->kind === \App\Models\Book::KIND_OWN && ! $book->isVersion() && $book->school_class_id;
    // Vorschau: bei einer Aufgabe zeigt das Kind seine eigene Version, sobald es sie angefangen hat
    // Preview: for an assignment the pupil sees their own version once they have started it
    $preview = $mine ?? $book;
    $stamp = $preview->isVersion() ? ($preview->submitted_at ? 'submitted' : ($preview->isReturned() ? 'returned' : null)) : null;
@endphp
<div class="book-card" data-subject="{{ $book->subject_id ?? 0 }}">
    <div class="cover-wrap">
        <a class="cover" href="{{ route('books.read', $preview) }}" aria-label="{{ $book->title }}"
           data-preview-id="{{ $preview->id }}" data-format="{{ $preview->format }}"></a>
        @if($stamp === 'submitted')
            <span class="stamp submitted" aria-hidden="true">✓ {{ __('Abgegeben') }}<small>{{ $preview->submitted_at->format('d.m.Y') }}</small></span>
        @elseif($stamp === 'returned')
            <span class="stamp returned" aria-hidden="true">↩ {{ __('zurückgegeben') }}</span>
        @endif
        <details class="card-menu">
            <summary aria-label="{{ __('Mehr') }}" title="{{ __('Mehr') }}">⋯</summary>
            <div class="menu">
                @if($book->isTemplate() && $manage)
                    <a href="{{ route('books.versions', $book) }}">📋 {{ __('Versionen der Kinder') }}</a>
                @endif
                @if($book->logVisibleTo($me))
                    <a href="{{ route('books.log', $book) }}">🕘 {{ __('Änderungsprotokoll') }}</a>
                @endif
                @if($manage && ! $book->isVersion())
                    <button type="button" data-change-subject data-url="{{ route('books.update', $book) }}" data-class="{{ $book->school_class_id }}"
                            data-subject="{{ $book->subject_id }}" data-title="{{ $book->title }}">📂 {{ __('In anderes Fach') }}</button>
                @endif
                <a href="{{ route('books.print', $preview) }}" target="_blank">🖨 {{ __('Drucken / PDF') }}</a>
                <a href="{{ route('books.download', $preview) }}">💾 {{ __('Als Datei mitnehmen') }}</a>
                @unless($pupilTemplate)
                    <form method="post" action="{{ route('books.copy', $book) }}">@csrf<button>📑 {{ __('Kopie anlegen') }}</button></form>
                @endunless
                @if($mine)
                    <form method="post" action="{{ route('books.destroy', $mine) }}"
                          onsubmit="return confirm(@js(__('Deine Version von „:titel“ löschen? Alles, was du darin gemacht hast, ist dann weg. Danach kannst du die Aufgabe neu anfangen.', ['titel' => $book->title])))">
                        @csrf @method('delete')<button style="color:var(--danger)">🗑 {{ __('Meine Version löschen') }}</button>
                    </form>
                @endif
                @if($manage)
                    <form method="post" action="{{ route('books.destroy', $book) }}"
                          onsubmit="return confirm(@js(__('„:titel“ wirklich löschen? Das geht nicht rückgängig.', ['titel' => $book->title])))">
                        @csrf @method('delete')<button style="color:var(--danger)">🗑 {{ __('Löschen') }}</button>
                    </form>
                @endif
            </div>
        </details>
    </div>
    <div class="title">{{ $book->title }}</div>
    <div class="meta">
        @if($book->subject && ! ($withoutSubject ?? false))<span class="badge subject">{{ $book->subject->symbol }} {{ $book->subject->name }}</span>@endif
        @if($book->isClassBook())<span class="badge kind">👥 {{ __('Klassenbuch') }}</span>
        @elseif($book->isTemplate())<span class="badge kind">{{ $pupilTemplate ? '📝 ' . __('Aufgabe') : '📋 ' . __('Vorlage') }}</span>@endif
        @if($book->isVersion())<span class="badge kind" title="{{ $book->template?->title }}">📋 {{ __('Version') }}</span>@endif
        @if($mine?->submitted_at)<span class="badge ok">✓ {{ __('abgegeben') }}</span>
        @elseif($mine?->isReturned())<span class="badge returned" @if($mine->return_note) title="{{ $mine->return_note }}" @endif>↩️ {{ __('zurückgegeben') }}</span>
        @elseif($mine)<span class="badge teacher">{{ __('in Arbeit') }}</span>@endif
        @if($book->submitted_at)<span class="badge ok">✓ {{ __('abgegeben') }}</span>
        @elseif($book->isReturned())<span class="badge returned" @if($book->return_note) title="{{ $book->return_note }}" @endif>↩️ {{ __('zurückgegeben') }}</span>@endif
        @if($shareable)
        @elseif($book->kind === \App\Models\Book::KIND_OWN && $book->sharing === 'class')<span class="badge ok">{{ __('mit der Klasse geteilt') }}</span>
        @elseif($book->kind === \App\Models\Book::KIND_OWN && $book->sharing === 'teacher' && ! $book->isVersion())<span class="badge teacher">{{ __('mit der Lehrkraft geteilt') }}</span>@endif
    </div>
    <div class="meta">
        @if(($showAuthor ?? false) && ! $book->isClassBook()){{ $book->owner->name }} · @endif
        @if($book->isClassBook() && $book->schoolClass){{ __('Klasse :name', ['name' => $book->schoolClass->name]) }} · @endif
        {{ $book->updated_at->format('d.m.Y') }}
        @if($book->isTemplate() && $manage)
            · {{ __(':n Versionen', ['n' => $book->versions->count()]) }}
        @endif
    </div>
    <div class="buttons">
        @if($pupilTemplate)
            <form method="post" action="{{ route('books.start', $book) }}" class="inline-form">@csrf
                <button class="small primary">{{ $mine ? __('Weiter') : __('Starten') }}</button></form>
            <a class="btn small blue" href="{{ route('books.read', $preview) }}" @if($mine) title="{{ __('Deine Version ansehen') }}" @endif>👀 {{ __('Ansehen') }}</a>
        @else
            <a class="btn small blue" href="{{ route('books.read', $book) }}">📖 {{ __('Lesen') }}</a>
            @if($canEdit)<a class="btn small primary" href="{{ route('books.edit', $book) }}">✏️ {{ __('Bearbeiten') }}</a>@endif
        @endif
    </div>
    @if($shareable)
        {{-- Zwei Knöpfe zum Teilen: nochmal tippen hebt das Teilen auf

             Two share buttons: tapping again stops sharing --}}
        <div class="share">
            <span class="share-title">{{ __('Teilen') }}</span>
            @foreach(['teacher' => ['🍎', __('Lehrkraft'), __('Mit der Lehrkraft teilen')], 'class' => ['👥', __('Klasse'), __('Mit der Klasse teilen')]] as $level => [$icon, $short, $long])
                @php($on = $book->sharing === $level || ($level === 'teacher' && $book->sharing === 'class'))
                @php($unshare = $book->sharing === $level && $book->user_id != $me->id)
                <form method="post" action="{{ route('books.update', $book) }}" class="inline-form"
                      @if($unshare) onsubmit="return confirm(@js(__('Das Teilen von „:titel“ (von :name) wirklich aufheben?', ['titel' => $book->title, 'name' => $book->owner->first_name])))" @endif>
                    @csrf @method('patch')
                    <input type="hidden" name="sharing" value="{{ $book->sharing === $level ? 'private' : $level }}">
                    <button class="share-button {{ $on ? 'on' : '' }}" aria-pressed="{{ $on ? 'true' : 'false' }}" aria-label="{{ $long }}"
                            title="{{ $level === 'teacher' && $book->sharing === 'class' ? __('Mit der Klasse geteilt — die Lehrkraft sieht es auch.') : $long }}"
                            @if($level === 'teacher' && $book->sharing === 'class') disabled @endif>{{ $icon }} {{ $short }}</button>
                </form>
            @endforeach
        </div>
    @endif
</div>

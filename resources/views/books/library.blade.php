@extends('layouts.app')
@section('title', __('Bibliothek'))
@section('content')
    <div class="lib-head">
        <h1>{{ __('Hallo :name!', ['name' => $user->displayName()]) }} 👋</h1>
    </div>
    @if($classInfo)
        <p class="class-info">🏫 {{ __('Klasse :name', ['name' => $classInfo['name']]) }}
            @if($classInfo['teachers']->isNotEmpty()) · ★ {{ trans_choice('Klassenlehrkraft: :namen|Klassenlehrkräfte: :namen', $classInfo['teachers']->count(), ['namen' => $classInfo['teachers']->map->name->join(' ' . __('und') . ' ')]) }}@endif
        </p>
    @endif
    @if($signIn)
        <div class="sign-in-bar" aria-label="{{ __('Anmeldung der Klassen') }}">
            @foreach($signIn as $a)
                <a href="{{ route('classes.sessions', $a['class']) }}" class="session-pill {{ $a['session'] ? 'on' : 'off' }}"
                   title="{{ $a['session'] ? __('Freigeschaltet: :grund', ['grund' => $a['session']->reason]) : __('Anmeldung freischalten') }}">
                    {{ $a['session'] ? '🔓' : '🔒' }} {{ $a['class']->name }}:
                    {{ $a['session'] ? __('bis :ende', ['ende' => $a['session']->ends_at->isToday() ? $a['session']->ends_at->format('H:i') : $a['session']->ends_at->format('d.m. H:i')]) : __('gesperrt') }}
                </a>
            @endforeach
        </div>
    @endif

    {{-- Jeder Bereich lässt sich einklappen; der Zustand wird je Browser gemerkt

         Every section can be collapsed; the state is remembered per browser --}}
    @unless($bySubject)
    <details class="library-section" data-section="mine" open>
        <summary><h2>📘 {{ __('Meine Bücher') }} <span class="count">{{ $mine->count() }}</span></h2></summary>
        <div class="shelf">
            <button type="button" class="book-card new-card" data-new-book data-subject="">
                <span>＋</span>{{ __('Neues Buch') }}
            </button>
            @foreach($mine as $book)
                @include('partials.book-card', ['book' => $book])
            @endforeach
        </div>
    </details>
    @endunless

    @foreach($sections as $s)
        <details class="library-section" data-section="{{ $s['key'] }}" id="section-{{ $s['key'] }}" @unless($s['collapsed'] ?? false) open @endunless>
            <summary><h2>@if(! empty($s['symbol'])){{ $s['symbol'] }} @endif{{ $s['title'] }} <span class="count">{{ $s['count'] ?? $s['books']->count() }}</span></h2></summary>
            @if(! empty($s['classTeachers']) && $s['classTeachers']->isNotEmpty())
                <p class="class-info">★ {{ trans_choice('Klassenlehrkraft: :namen|Klassenlehrkräfte: :namen', $s['classTeachers']->count(), ['namen' => $s['classTeachers']->map->name->join(' ' . __('und') . ' ')]) }}</p>
            @endif
            @if($s['notice'])<p class="hint section-notice">{{ $s['notice'] }}</p>@endif
            @if(! empty($s['class']) && ! empty($subjectsByClass[$s['class']]))
                <div class="filter-chips subject-chips" data-subject-filter role="group" aria-label="{{ __('Fach') }}">
                    <button type="button" class="chip on" data-subject="all">{{ __('Alle Fächer') }}</button>
                    @foreach($subjectsByClass[$s['class']] as $f)
                        <button type="button" class="chip {{ $f['mine'] ? 'mine' : '' }}" data-subject="{{ $f['id'] }}" @if($f['mine']) title="{{ __('Dein Fach') }}" @endif>{{ $f['symbol'] }} {{ $f['name'] }}</button>
                    @endforeach
                    <button type="button" class="chip" data-subject="0">🗂️ {{ __('Ohne Fach') }}</button>
                </div>
            @endif
            @if(isset($s['pupils']))
                @include('partials.pupil-shelf', ['s' => $s])
            @elseif(! empty($s['groups']))
                @foreach($s['groups'] as $g)
                    <div class="section-group">
                        <h3 class="group-title">{{ $g['symbol'] }} {{ $g['title'] }} <span class="count">{{ $g['books']->count() }}</span></h3>
                        <div class="shelf">
                            @if(! empty($g['new']))
                                <button type="button" class="book-card new-card" data-new-book data-subject="{{ $s['subject'] ?? '' }}">
                                    <span>＋</span>{{ __('Neues Buch') }}
                                </button>
                            @endif
                            @foreach($g['books'] as $book)
                                @include('partials.book-card', ['book' => $book, 'showAuthor' => $g['key'] !== 'mine', 'myVersions' => $g['myVersions'] ?? collect(), 'withoutSubject' => isset($s['subject'])])
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @elseif($s['books']->isEmpty())
                <div class="empty">{{ $s['empty'] ?? __('Hier ist noch nichts.') }}</div>
            @else
                <div class="shelf">
                    @foreach($s['books'] as $book)
                        @include('partials.book-card', ['book' => $book, 'showAuthor' => true, 'myVersions' => $s['myVersions'] ?? collect()])
                    @endforeach
                </div>
            @endif
        </details>
    @endforeach

    <dialog id="new-book">
        <form method="post" action="{{ route('books.store') }}">
            @csrf
            <h2 style="margin-top:0">📘 {{ __('Neues Buch') }}</h2>
            <label for="title">{{ __('Wie heißt dein Buch?') }}</label>
            <input type="text" id="title" name="title" maxlength="120" placeholder="{{ __('Mein Buch') }}">
            <label>{{ __('Welche Form?') }}</label>
            <div class="format-choice">
                <label><input type="radio" name="format" value="portrait" checked><i style="width:34px;height:48px"></i>{{ __('Hoch') }}</label>
                <label><input type="radio" name="format" value="square"><i style="width:44px;height:44px"></i>{{ __('Quadrat') }}</label>
                <label><input type="radio" name="format" value="landscape"><i style="width:52px;height:37px"></i>{{ __('Quer') }}</label>
            </div>
            @if($user->canLead() && $classes->isNotEmpty())
                <label>{{ __('Was für ein Buch?') }}</label>
                <div class="kind-choice">
                    <label><input type="radio" name="kind" value="own" checked><b>📖 {{ __('Eigenes Buch') }}</b><span>{{ __('Nur für dich — teilen kannst du es später.') }}</span></label>
                    <label><input type="radio" name="kind" value="class"><b>👥 {{ __('Klassenbuch') }}</b><span>{{ __('Die ganze Klasse schreibt gemeinsam darin.') }}</span></label>
                    <label><input type="radio" name="kind" value="template"><b>📋 {{ __('Vorlage') }}</b><span>{{ __('Jedes Kind legt daraus seine eigene Version an, die du sehen kannst.') }}</span></label>
                </div>
                <label for="new-class">{{ __('Klasse') }}</label>
                <select id="new-class" name="school_class_id">
                    @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                </select>
            @endif
            @if(collect($subjectsByClass)->flatten(1)->isNotEmpty())
                <label for="new-subject">{{ __('Fach') }}</label>
                <select id="new-subject" name="subject_id"></select>
            @endif
            <div class="actions">
                <button class="primary">{{ __('Los geht\'s') }}</button>
                <button type="button" onclick="this.closest('dialog').close()">{{ __('Abbrechen') }}</button>
            </div>
        </form>
    </dialog>

    <dialog id="subject-dialog">
        <form method="post" action="">
            @csrf @method('patch')
            <h2 style="margin-top:0">📂 {{ __('In anderes Fach') }}</h2>
            <p class="sd-title"></p>
            <label for="sd-subject">{{ __('Fach') }}</label>
            <select id="sd-subject" name="subject_id"></select>
            <div class="actions">
                <button class="primary">📂 {{ __('Verschieben') }}</button>
                <button type="button" onclick="this.closest('dialog').close()">{{ __('Abbrechen') }}</button>
            </div>
        </form>
    </dialog>
@endsection
@push('scripts')
    <script src="{{ asset('js/book.js') }}?v={{ config('version.build') }}"></script>
    <script>
        BDB.base = @json(rtrim(url('/'), '/'));
        BDB.drawPreviews();

        // Fächer je Klasse (für „Neues Buch“ und „In anderes Fach“)
        // Subjects per class (for “New book” and “Move to another subject”)
        (function () {
            const SUBJECTS = @json($subjectsByClass);
            const ALL = @json($allSubjects->map(fn ($f) => ['id' => $f->id, 'name' => $f->name, 'symbol' => $f->symbol])->values());
            const NONE = @json('🗂️ ' . __('Ohne Fach'));
            const DEFAULT_CLASS = @json($user->isPupil() ? $user->school_class_id : null);
            function fill(select, classId, value) {
                const list = classId ? (SUBJECTS[classId] || []) : ALL;
                select.textContent = '';
                const empty = document.createElement('option'); empty.value = ''; empty.textContent = NONE; select.appendChild(empty);
                list.forEach((f) => {
                    const o = document.createElement('option');
                    o.value = f.id; o.textContent = f.symbol + ' ' + f.name + (f.mine ? ' ★' : '');
                    select.appendChild(o);
                });
                select.value = value && list.some((f) => String(f.id) === String(value)) ? value : '';
            }

            const dlg = document.getElementById('new-book');
            const newSubject = document.getElementById('new-subject');
            const newClass = document.getElementById('new-class');
            const classNow = () => (newClass ? newClass.value : DEFAULT_CLASS);
            if (newClass && newSubject) newClass.addEventListener('change', () => fill(newSubject, classNow(), newSubject.value));
            document.querySelectorAll('[data-new-book]').forEach((b) => b.addEventListener('click', () => {
                if (newSubject) fill(newSubject, classNow(), b.dataset.subject);
                dlg.showModal();
            }));

            const sd = document.getElementById('subject-dialog');
            document.addEventListener('click', (e) => {
                const b = e.target.closest('[data-change-subject]');
                if (!b) return;
                e.preventDefault();
                b.closest('details')?.removeAttribute('open');
                sd.querySelector('form').action = b.dataset.url;
                sd.querySelector('.sd-title').textContent = b.dataset.title;
                fill(sd.querySelector('select'), b.dataset.class || null, b.dataset.subject);
                sd.showModal();
            });

            // Lehrkraft: Fach-Filter je Bereich (wirkt mit dem Art-Filter der Kinderliste zusammen)
            // Teacher: subject filter per section (works together with the kind filter of the pupils' list)
            document.querySelectorAll('[data-subject-filter]').forEach((bar) => {
                const section = bar.closest('details.library-section');
                bar.querySelectorAll('[data-subject]').forEach((c) => c.addEventListener('click', () => {
                    bar.querySelectorAll('[data-subject]').forEach((x) => x.classList.toggle('on', x === c));
                    section.dataset.subjectFilter = c.dataset.subject;
                    section.dispatchEvent(new CustomEvent('bdb-filter'));
                }));
                section.addEventListener('bdb-filter', () => {
                    // Kinderliste filtert selbst
                    // pupils' list filters itself
                    if (section.querySelector('[data-pupils-tools]')) return;
                    const f = section.dataset.subjectFilter || 'all';
                    section.querySelectorAll('.section-group').forEach((g) => {
                        let n = 0;
                        g.querySelectorAll('.book-card[data-subject]').forEach((k) => {
                            const fits = f === 'all' || k.dataset.subject === f;
                            k.hidden = !fits;
                            if (fits) n++;
                        });
                        g.hidden = !n;
                    });
                    BDB.drawPreviews();
                });
            });
        })();

        // eingeklappte Bereiche merken (nur in diesem Browser; alter Schlüssel „bdb-regale-zu“ wird übernommen)
        // remember collapsed sections (in this browser only; the old key "bdb-regale-zu" is taken over)
        (function () {
            const key = 'bdb-sections-collapsed';
            let collapsed = [];
            try { collapsed = JSON.parse(BDB.stored(key, 'bdb-regale-zu') || '[]'); } catch (e) { /* kein Speicher / no storage */ }
            document.querySelectorAll('details.library-section').forEach((d) => {
                if (collapsed.includes(d.dataset.section)) d.open = false;
                d.addEventListener('toggle', () => {
                    collapsed = collapsed.filter((k) => k !== d.dataset.section);
                    if (!d.open) collapsed.push(d.dataset.section);
                    BDB.store(key, JSON.stringify(collapsed));
                    if (d.open) BDB.drawPreviews();
                });
            });
            // Bücher der Kinder: alle auf/zu und Filter (Aufgaben, abgegeben, geteilt, nicht geteilt)
            // Pupils' books: expand/collapse all and filters (assignments, handed in, shared, not shared)
            document.querySelectorAll('[data-pupils-tools]').forEach((w) => {
                const section = w.closest('details.library-section');
                const pupils = () => section.querySelectorAll('details.pupil-section');
                w.querySelectorAll('[data-all]').forEach((b) => b.addEventListener('click', () => {
                    pupils().forEach((d) => { if (d.open !== (b.dataset.all === 'open')) { d.open = b.dataset.all === 'open'; d.dispatchEvent(new Event('toggle')); } });
                }));
                const filter = () => {
                    const f = (w.querySelector('[data-filter].on') || {}).dataset?.filter || 'all';
                    const subject = section.dataset.subjectFilter || 'all';
                    let visible = 0;
                    pupils().forEach((d) => {
                        let inPupil = 0;
                        d.querySelectorAll('.section-group').forEach((g) => {
                            let inGroup = 0;
                            g.querySelectorAll('[data-kind]').forEach((k) => {
                                const subjectFits = subject === 'all' || (k.querySelector('.book-card') || {}).dataset?.subject === subject;
                                const fits = subjectFits && (f === 'all' || k.dataset.kind === f || (f === 'submitted' && k.dataset.status === 'submitted'));
                                k.hidden = !fits;
                                if (fits) inGroup++;
                            });
                            g.hidden = !inGroup;
                            inPupil += inGroup;
                        });
                        d.hidden = !inPupil;
                        if (inPupil && (f !== 'all' || subject !== 'all') && !d.open) d.open = true;
                        visible += inPupil;
                    });
                    section.querySelector('.pupils-empty-filter').hidden = visible > 0;
                    BDB.drawPreviews();
                };
                w.querySelectorAll('[data-filter]').forEach((b) => b.addEventListener('click', () => {
                    w.querySelectorAll('[data-filter]').forEach((x) => x.classList.toggle('on', x === b));
                    filter();
                }));
                section.addEventListener('bdb-filter', filter);
            });

            const target = location.hash && document.querySelector(location.hash);
            if (target && target.matches('details.library-section')) { target.open = true; target.scrollIntoView(); }
        })();
    </script>
@endpush

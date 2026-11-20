{{-- Cara, die Begleiterin (Figur und Ton: docs/CARA.md): eine schwarze Katze mit
     bernsteingelben Augen. Zwei Haltungen in einer Zeichnung: eingerollt und
     schlafend (k-rolle, k-zzz) und wach sitzend (k-sitzt). Sie liegt schlafend da
     (Klasse „asleep“) und die Blase ist verborgen, bis man sie antippt; die Daten für
     companion.js stehen im data-Attribut. Modus page/login/editor/reading; im Buch
     kommen die Ereignisse aus companion-book.js dazu.

     Cara, the companion (character and tone: docs/CARA.md): a black cat with amber
     eyes. Two postures in one drawing: curled up asleep (k-rolle, k-zzz) and sitting
     awake (k-sitzt). She lies asleep (class "asleep") and the bubble stays hidden
     until she is tapped; the data for companion.js is in the data attribute. Mode
     page/login/editor/reading; inside a book companion-book.js adds the events. --}}
@php($companionMode = $companionMode ?? (isset($companionBook) ? 'editor' : (auth()->check() ? 'page' : 'login')))
@php($companionData = auth()->check()
    ? \App\Support\Companion::data(auth()->user(), request()->route()?->getName(), (bool) session('ok'), $companionBook ?? null, $companionMode)
    : \App\Support\Companion::guest())
<div class="companion asleep on-{{ $companionMode }}" id="companion" hidden data-prefix="bdb-companion" data-companion='@json($companionData)'>
    <button type="button" class="companion-figure" aria-label="{{ $companionData['texts']['asleep'] }}">
        <svg viewBox="0 0 120 152" width="96" height="122" aria-hidden="true" focusable="false">
            <g class="k-rolle">
            <ellipse class="k-schatten" cx="72" cy="146" rx="48" ry="6"/>
            <path class="k-fell" d="M48 118
            C48 98 62 86 82 86
            C104 86 116 100 116 115
            C116 130 104 141 86 141
            L56 141
            C46 141 42 133 48 118 Z"/>
            <path class="k-licht" d="M64 92 C78 84 100 86 112 100"/>
            <g transform="translate(38 108) rotate(-7)">
            <path class="k-umriss" d="M-19 -11.6 L-21.5 -33.1 L-2.5 -18.2 Z"/>
            <path class="k-umriss" d="M19 -11.6 L21.5 -33.1 L2.5 -18.2 Z"/>
            <circle class="k-umriss" cx="0" cy="0" r="24"/>
            <path class="k-ohr" d="M-17.1 -15.6 L-18.6 -28.2 L-7.2 -19.3 Z"/>
            <path class="k-ohr" d="M17.1 -15.6 L18.6 -28.2 L7.2 -19.3 Z"/>
            <g class="k-brauen">
            <path d="M-20.5 -28 L-23 -31.4"/><path d="M-18.4 -30 L-20 -33.8"/>
            <path d="M20.5 -28 L23 -31.4"/><path d="M18.4 -30 L20 -33.8"/>
            </g>
            <ellipse class="k-wange" cx="-16.6" cy="9.1" rx="5.8" ry="3.6"/>
            <ellipse class="k-wange" cx="16.6" cy="9.1" rx="5.8" ry="3.6"/>
            <ellipse class="k-schnauze" cx="-4.6" cy="12" rx="6.3" ry="4.3"/>
            <ellipse class="k-schnauze" cx="4.6" cy="12" rx="6.3" ry="4.3"/>
            <path class="k-strich" d="M-16.9 0 q7 6 14 0"/>
            <path class="k-strich" d="M2.9 0 q7 6 14 0"/>
            <path class="k-lid"    d="M-16.4 -2.2 q6.8 2.1 13 0"/>
            <path class="k-lid"    d="M3.4 -2.2 q6.8 2.1 13 0"/>
            <path class="k-nase" d="M-3 8.8 L3 8.8 L0 12.7 Z"/>
            <path class="k-mund" d="M0 12.7 v1.7"/>
            <path class="k-mund" d="M0 14.4 q-2.8 2.4 -5.1 .5"/>
            <path class="k-mund" d="M0 14.4 q2.8 2.4 5.1 .5"/>
            <g class="k-haare">
            <path d="M-12.4 9.1 C-19.9 7.4 -27.3 5 -33.9 1.7"/>
            <path d="M-12.4 11.6 C-20.7 11.6 -28.1 11.6 -35.6 10.8"/>
            <path d="M-11.6 14.1 C-19 15.7 -25.7 18.2 -32.3 20.7"/>
            <path d="M12.4 9.1 C19.9 7.4 27.3 5 33.9 1.7"/>
            <path d="M12.4 11.6 C20.7 11.6 28.1 11.6 35.6 10.8"/>
            <path d="M11.6 14.1 C19 15.7 25.7 18.2 32.3 20.7"/>
            </g>
            <g class="k-brauen">
            <path d="M-9.9 -6.6 L-13.2 -9.9"/><path d="M-5 -8.3 L-7.4 -11.6"/>
            <path d="M9.9 -6.6 L13.2 -9.9"/><path d="M5 -8.3 L7.4 -11.6"/>
            </g>
            </g>
            <g class="k-wedel-schlaf">
            <path class="k-saum"    d="M116 112 C120 132 100 143 72 140 C64 139 58 137 54 134"/>
            <path class="k-schwanz" d="M116 112 C120 132 100 143 72 140 C64 139 58 137 54 134"/>
            </g>
            </g>
            <g class="k-sitzt">
            <ellipse class="k-schatten" cx="54" cy="144" rx="33" ry="6.2"/>
            <g class="k-wedel">
            <path class="k-schwanz-duenn" d="M74 132 C100 132 109 103 99 83"/>
            <g class="k-wedel-spitze">
            <path class="k-schwanz-duenn" d="M99 83 C94 72 83 75 87 86"/>
            </g>
            </g>
            <g class="k-rumpf">
            <path class="k-fell" d="M36 58
            C26 76 22 112 29 130
            C34 141 70 141 75 130
            C82 112 78 76 68 58 Z"/>
            <path class="k-licht" d="M31 82 C26 100 26 118 30 128"/>
            </g>
            <ellipse class="k-fell" cx="42" cy="134" rx="10" ry="5.6"/>
            <ellipse class="k-fell" cx="62" cy="134" rx="10" ry="5.6"/>
            <path class="k-strich-fein" d="M39 131 v5 M45 131 v5 M59 131 v5 M65 131 v5"/>
            <g class="k-kopf">
            <path class="k-fell k-ohr-l" d="M29 30 L26 4 L49 22 Z"/>
            <path class="k-fell k-ohr-r" d="M75 30 L78 4 L55 22 Z"/>
            <circle class="k-fell" cx="52" cy="44" r="29"/>
            <path class="k-ohr k-ohr-l" d="M31.3 25.2 L43.3 20.7 L29.5 9.9 Z"/>
            <path class="k-ohr k-ohr-r" d="M72.7 25.2 L60.7 20.7 L74.5 9.9 Z"/>
            <g class="k-brauen">
            <path class="k-ohr-l" d="M28 12 L25 8"/><path class="k-ohr-l" d="M30.5 9.5 L28.5 5"/>
            <path class="k-ohr-r" d="M76 12 L79 8"/><path class="k-ohr-r" d="M73.5 9.5 L75.5 5"/>
            </g>
            <ellipse class="k-wange" cx="32" cy="55" rx="7" ry="4.4"/>
            <ellipse class="k-wange" cx="72" cy="55" rx="7" ry="4.4"/>
            <ellipse class="k-schnauze" cx="46.5" cy="58.5" rx="7.6" ry="5.2"/>
            <ellipse class="k-schnauze" cx="57.5" cy="58.5" rx="7.6" ry="5.2"/>
            <path class="k-glanz-fein" d="M41 55.5 q5.5 -2.6 11 0 q5.5 -2.6 11 0"/>
            <g class="k-augen">
            <ellipse class="k-auge" cx="40" cy="46" rx="8.6" ry="9.2"/>
            <ellipse class="k-auge" cx="64" cy="46" rx="8.6" ry="9.2"/>
            <circle class="k-pupille" cx="40" cy="47" r="5.8"/>
            <circle class="k-pupille" cx="64" cy="47" r="5.8"/>
            <circle class="k-glanz" cx="37" cy="43" r="3"/>
            <circle class="k-glanz" cx="61" cy="43" r="3"/>
            <circle class="k-glanz k-glanz--klein" cx="43" cy="50.5" r="1.5"/>
            <circle class="k-glanz k-glanz--klein" cx="67" cy="50.5" r="1.5"/>
            </g>
            <path class="k-nase" d="M48.4 54.6 L55.6 54.6 L52 59.4 Z"/>
            <path class="k-mund" d="M52 59.4 v2"/>
            <path class="k-mund" d="M52 61.4 q-3.4 2.9 -6.2 .6"/>
            <path class="k-mund" d="M52 61.4 q3.4 2.9 6.2 .6"/>
            <g class="k-haare">
            <path d="M37 55 C28 53 19 50 11 46"/>
            <path d="M37 58 C27 58 18 58 9 57"/>
            <path d="M38 61 C29 63 21 66 13 69"/>
            <path d="M67 55 C76 53 85 50 93 46"/>
            <path d="M67 58 C77 58 86 58 95 57"/>
            <path d="M66 61 C75 63 83 66 91 69"/>
            </g>
            <g class="k-brauen">
            <path d="M40 36 L36 32"/><path d="M46 34 L43 30"/>
            <path d="M64 36 L68 32"/><path d="M58 34 L61 30"/>
            </g>
            </g>
            </g>
            <g class="k-zzz" aria-hidden="true">
            <text class="k-z k-z1" x="74" y="70">z</text>
            <text class="k-z k-z2" x="87" y="54">z</text>
            </g>
        </svg>
    </button>
    <div class="companion-bubble" role="status" aria-live="polite" hidden>
        <p class="companion-text"></p>
        <div class="companion-buttons">
            <button type="button" class="companion-next">{{ $companionData['texts']['next'] }} ›</button>
            @if(! empty($companionData['link']))
                <a class="companion-link" href="{{ $companionData['link']['url'] }}" target="_blank" rel="noopener">{{ $companionData['link']['text'] }} ↗</a>
            @endif
            <button type="button" class="companion-sleep">💤 {{ $companionData['texts']['sleep'] }}</button>
        </div>
        <button type="button" class="companion-close" aria-label="{{ $companionData['texts']['close'] }}">×</button>
    </div>
</div>
<script src="{{ asset('js/companion.js') }}?v={{ config('version.build') }}" defer></script>
@if(! empty($companionData['events']))<script src="{{ asset('js/companion-book.js') }}?v={{ config('version.build') }}" defer></script>@endif

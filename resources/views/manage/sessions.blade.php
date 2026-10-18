@extends('layouts.app')
@section('title', __('Anmeldung') . ' · ' . $class->name)
@section('content')
    <p><a href="{{ route('classes.index') }}">← {{ __('Klassen') }}</a></p>
    <h1>🔓 {{ __('Anmeldung der Klasse :name', ['name' => $class->name]) }}</h1>

    @if(! \App\Support\SessionRequirement::required())
        <div class="flash info">{{ __('Die Sitzungspflicht ist in den Einstellungen ausgeschaltet — Kinder können sich gerade jederzeit anmelden.') }}</div>
    @endif

    @if($active)
        @php($signedIn = $active->logins->sortByDesc('created_at')->unique('user_id'))
        <div class="panel session-active">
            <div class="session-head">
                <span class="session-status on">🔓 {{ __('Freigeschaltet') }}</span>
                <span>{{ __('bis :ende', ['ende' => $active->ends_at->isToday() ? $active->ends_at->format('H:i') . ' ' . __('Uhr') : $active->ends_at->translatedFormat('D d.m.Y, H:i') . ' ' . __('Uhr')]) }}
                    <small class="hint">({{ __('noch :zeit', ['zeit' => $active->ends_at->diffForHumans(now(), \Carbon\CarbonInterface::DIFF_ABSOLUTE, false, 2)]) }})</small></span>
            </div>
            <div class="session-code-box">
                <span>{{ __('Sitzungscode für die Kinder') }}</span>
                <b class="session-code">{{ trim(chunk_split($active->plainCode() ?? '——————', 3, ' ')) }}</b>
                <small>{{ __('Die Kinder geben ihn nach ihrem Passwort ein.') }}</small>
            </div>
            <table class="list compact">
                <tr><th>{{ __('Grund') }}</th><td>{{ $active->reason }}</td></tr>
                <tr><th>{{ __('Gestartet') }}</th><td>{{ $active->starts_at->format('d.m.Y H:i') }} · {{ $active->starter?->name ?? '—' }}</td></tr>
                @foreach($active->extensions as $v)
                    <tr><th>{{ __('Verlängert') }}</th><td>{{ $v->created_at->format('d.m. H:i') }} · {{ $v->user?->name ?? '—' }}: {{ $v->old_end->format('d.m. H:i') }} → <b>{{ $v->new_end->format('d.m. H:i') }}</b></td></tr>
                @endforeach
                <tr><th>{{ __('Angemeldet') }}</th><td><b>{{ __(':n von :m Kindern', ['n' => $signedIn->count(), 'm' => $pupils->count()]) }}</b></td></tr>
            </table>
            @if($signedIn->isNotEmpty())
                <details class="session-list" open>
                    <summary>{{ __('Wer hat sich angemeldet?') }}</summary>
                    <ul>
                        @foreach($signedIn as $a)
                            <li><span>{{ $a->user?->name ?? '—' }}</span><small>{{ $a->created_at->format('H:i') }}</small></li>
                        @endforeach
                    </ul>
                </details>
            @endif

            <form method="post" action="{{ route('classes.sessions.extend', [$class, $active]) }}" class="session-extend">
                @csrf
                <label>⏱️ {{ __('Verlängern') }}</label>
                @php($extendBy = old('extend_by', '30'))
                <div class="duration-choice">
                    @foreach(\App\Models\ClassSession::EXTEND_MINUTES as $m)
                        <label><input type="radio" name="extend_by" value="{{ $m }}" @checked($extendBy == $m)><b>+{{ $m }}</b><span>{{ __('Minuten') }}</span></label>
                    @endforeach
                    <label><input type="radio" name="extend_by" value="days" @checked($extendBy === 'days')><b>📅</b><span>{{ __('bis zu einem Tag') }}</span></label>
                </div>
                <div class="duration-date" data-for="extend_by" @if($extendBy !== 'days') hidden @endif>
                    <label for="v-date">{{ __('Bis zu welchem Tag? (endet um :uhr Uhr)', ['uhr' => \App\Models\ClassSession::END_TIME]) }}</label>
                    <select id="v-date" name="date">
                        @for($i = 0; $i <= \App\Models\ClassSession::MAX_DAYS; $i++)
                            @php($day = $today->copy()->addDays($i))
                            @php($tooEarly = \App\Models\ClassSession::endOnDay($day->toDateString())->lte($active->ends_at))
                            <option value="{{ $day->toDateString() }}" @disabled($tooEarly) @selected(old('date') === $day->toDateString())>
                                {{ $i === 0 ? __('heute') : ($i === 1 ? __('morgen') : $day->translatedFormat('l')) }}, {{ $day->format('d.m.') }} · {{ \App\Models\ClassSession::END_TIME }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="actions"><button class="primary">⏱️ {{ __('Sitzung verlängern') }}</button></div>
                <p class="hint">{{ __('Der Sitzungscode bleibt gleich, angemeldete Kinder bleiben angemeldet. Jede Lehrkraft der Klasse kann verlängern; das wird dokumentiert.') }}</p>
            </form>

            <form method="post" action="{{ route('classes.sessions.close', [$class, $active]) }}" class="session-close"
                  onsubmit="return confirm(@js(__('Sitzung jetzt beenden? Alle angemeldeten Kinder der Klasse :name werden abgemeldet.', ['name' => $class->name])))">
                @csrf
                <label for="close_reason">{{ __('Vorzeitig beenden — Grund') }}</label>
                <div class="row">
                    <input type="text" id="close_reason" name="close_reason" maxlength="200" required
                           placeholder="{{ __('z. B. Unterricht früher beendet, Notfall, falsche Klasse') }}" value="{{ old('close_reason') }}">
                    <button class="danger auto">🔒 {{ __('Jetzt beenden') }}</button>
                </div>
                <p class="hint">{{ __('Jede Lehrkraft der Klasse kann die Sitzung beenden. Grund und Zeitpunkt werden dokumentiert.') }}</p>
            </form>
        </div>
    @else
        <div class="panel">
            <p class="session-status off">🔒 {{ __('Gesperrt — die Kinder können sich gerade nicht anmelden.') }}</p>
            <form method="post" action="{{ route('classes.sessions.start', $class) }}" class="session-start">
                @csrf
                <label for="reason">{{ __('Grund') }}</label>
                <input type="text" id="reason" name="reason" maxlength="200" required list="session-reasons" value="{{ old('reason') }}"
                       placeholder="{{ __('z. B. Unterricht, Hausaufgaben, Projektwoche') }}">
                <datalist id="session-reasons">
                    @foreach([__('Unterricht'), __('Hausaufgaben'), __('Projektarbeit'), __('Vertretungsstunde'), __('Lesezeit')] as $g)<option value="{{ $g }}">@endforeach
                </datalist>

                <label>{{ __('Wie lange?') }}</label>
                @php($duration = old('duration', '60'))
                <div class="duration-choice">
                    @foreach(\App\Models\ClassSession::MINUTES as $m)
                        <label><input type="radio" name="duration" value="{{ $m }}" @checked($duration == $m)><b>{{ $m }}</b><span>{{ __('Minuten') }}</span></label>
                    @endforeach
                    <label><input type="radio" name="duration" value="days" @checked($duration === 'days')><b>📅</b><span>{{ __('bis zu einem Tag') }}</span></label>
                </div>
                <div class="duration-date" @if($duration !== 'days') hidden @endif>
                    <label for="date">{{ __('Bis zu welchem Tag? (endet um :uhr Uhr)', ['uhr' => \App\Models\ClassSession::END_TIME]) }}</label>
                    <select id="date" name="date">
                        @for($i = 0; $i <= \App\Models\ClassSession::MAX_DAYS; $i++)
                            @php($day = $today->copy()->addDays($i))
                            @php($past = $i === 0 && now()->gte(\App\Models\ClassSession::endOnDay($day->toDateString())))
                            <option value="{{ $day->toDateString() }}" @selected(old('date', $today->copy()->addDay()->toDateString()) === $day->toDateString()) @disabled($past)>
                                {{ $i === 0 ? __('heute') : ($i === 1 ? __('morgen') : $day->translatedFormat('l')) }}, {{ $day->format('d.m.') }} · {{ \App\Models\ClassSession::END_TIME }}
                            </option>
                        @endfor
                    </select>
                </div>
                <p class="hint">{{ __('Beim Start entsteht ein 6-stelliger Sitzungscode. Die Kinder melden sich mit Passwort und diesem Code an. Endet die Sitzung, werden alle Kinder der Klasse abgemeldet.') }}</p>
                <div class="actions"><button class="primary">🔓 {{ __('Anmeldung freischalten') }}</button></div>
            </form>
        </div>
    @endif

    <div class="panel">
        <h2 style="margin-top:0">🕘 {{ __('Verlauf der letzten :n Tage', ['n' => \App\Models\ClassSession::KEEP_DAYS]) }}</h2>
        @if($history->isEmpty())
            <p class="hint">{{ __('Noch keine früheren Sitzungen.') }}</p>
        @else
            <div class="table-scroll">
            <table class="list session-history">
                <thead><tr><th>{{ __('Zeitraum') }}</th><th>{{ __('Gestartet von') }}</th><th>{{ __('Grund') }}</th><th>{{ __('Ende') }}</th><th>{{ __('Angemeldet') }}</th></tr></thead>
                <tbody>
                @foreach($history as $s)
                    @php($list = $s->logins->sortBy('created_at')->unique('user_id'))
                    <tr>
                        <td style="white-space:nowrap">{{ $s->starts_at->format('d.m. H:i') }} – {{ ($s->closed_at ?? $s->ends_at)->format($s->starts_at->isSameDay($s->closed_at ?? $s->ends_at) ? 'H:i' : 'd.m. H:i') }}</td>
                        <td>{{ $s->starter?->name ?? '—' }}</td>
                        <td>{{ $s->reason }}
                            @foreach($s->extensions as $v)<small class="hint" style="display:block">⏱️ {{ __('verlängert von :wer bis :ende', ['wer' => $v->user?->name ?? '—', 'ende' => $v->new_end->format('d.m. H:i')]) }}</small>@endforeach</td>
                        <td>
                            @if($s->closed_at)
                                <span class="badge returned">🔒 {{ __('vorzeitig beendet') }}</span>
                                <small class="hint" style="display:block">{{ $s->closer?->name ?? '—' }}: „{{ $s->close_reason }}“</small>
                            @else
                                <span class="badge">{{ __('abgelaufen') }}</span>
                            @endif
                        </td>
                        <td>
                            @if($list->isEmpty()) <span class="hint">0</span>
                            @else
                                <details class="session-list"><summary>{{ $list->count() }}</summary>
                                    <ul>@foreach($list as $a)<li><span>{{ $a->user?->name ?? '—' }}</span><small>{{ $a->created_at->format('d.m. H:i') }}</small></li>@endforeach</ul>
                                </details>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
        <p class="hint">{{ __('Sitzungen, Gründe und Anmeldungen werden nach :n Tagen automatisch gelöscht.', ['n' => \App\Models\ClassSession::KEEP_DAYS]) }}</p>
    </div>
@endsection
@push('scripts')
<script>
    ['duration', 'extend_by'].forEach((name) => document.querySelectorAll('input[name=' + name + ']').forEach((r) => r.addEventListener('change', () => {
        const box = r.closest('form').querySelector('.duration-date');
        if (box) box.hidden = r.closest('form').querySelector('input[name=' + name + ']:checked').value !== 'days';
    })));
</script>
@endpush

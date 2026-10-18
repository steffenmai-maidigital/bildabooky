{{-- Restzeit der Anmelde-Sitzung für Kinder; bei 0 lädt die Seite neu (und meldet damit ab)

     Remaining time of the pupils' sign-in session; at 0 the page reloads (and so signs out) --}}
@php($clockUser = auth()->user())
@if($clockUser && \App\Support\SessionRequirement::appliesTo($clockUser) && ($clockSession = \App\Models\ClassSession::find(session('class_session'))) && $clockSession->isActive())
    <span class="session-clock {{ $class ?? '' }}" data-remaining="{{ max(0, now()->diffInSeconds($clockSession->ends_at, false)) }}"
          title="{{ __('Deine Anmeldung endet :ende.', ['ende' => $clockSession->ends_at->isToday() ? __('um :uhr Uhr', ['uhr' => $clockSession->ends_at->format('H:i')]) : $clockSession->ends_at->translatedFormat('D d.m., H:i') . ' ' . __('Uhr')]) }}"
          role="timer" aria-live="off">⏳ <b>…</b></span>
    @once
    <script>
        (function () {
            const T = @json(['day' => __(':n Tag'), 'days' => __(':n Tage'), 'hours' => __(':n Std.')]);
            const start = Date.now();
            function text(s) {
                if (s >= 86400) {
                    const d = Math.floor(s / 86400), h = Math.floor((s % 86400) / 3600);
                    return (d === 1 ? T.day : T.days).replace(':n', d) + (h ? ' ' + T.hours.replace(':n', h) : '');
                }
                const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), x = s % 60;
                const zz = (n) => String(n).padStart(2, '0');
                return (h ? h + ':' + zz(m) : m) + ':' + zz(x);
            }
            function tick() {
                document.querySelectorAll('.session-clock[data-remaining]').forEach((u) => {
                    const remaining = Math.max(0, parseInt(u.dataset.remaining, 10) - Math.floor((Date.now() - start) / 1000));
                    u.querySelector('b').textContent = text(remaining);
                    u.classList.toggle('soon', remaining <= 300);
                    if (remaining === 0 && !u.dataset.done) { u.dataset.done = '1'; setTimeout(() => location.reload(), 1500); }
                });
            }
            tick();
            setInterval(tick, 1000);
        })();
    </script>
    @endonce
@endif

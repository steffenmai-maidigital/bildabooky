<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;

/**
 * Anmelde-Sitzungen einer Klasse: starten, sehen, vorzeitig beenden (mit Grund).
 * Dürfen alle Lehrkräfte der Klasse — und Admins.
 *
 * Sign-in sessions of a class: start, view, end early (with a reason).
 * Allowed for all teachers of the class — and admins.
 */
class ClassSessionController extends Controller
{
    private function mayManage(User $user, SchoolClass $class): bool
    {
        return $user->isAdmin() || in_array($class->id, $user->teachingClassIds(), true);
    }

    public function index(Request $request, SchoolClass $schoolClass)
    {
        abort_unless($this->mayManage($request->user(), $schoolClass), 403);
        $active = ClassSession::activeFor($schoolClass->id);
        $active?->load('starter', 'logins.user', 'extensions.user');
        $pupils = $schoolClass->pupils()->get();

        return view('manage.sessions', [
            'class'   => $schoolClass,
            'active'  => $active,
            'pupils'  => $pupils,
            'history' => ClassSession::where('school_class_id', $schoolClass->id)
                ->where('starts_at', '>=', now()->subDays(ClassSession::KEEP_DAYS))
                ->when($active, fn ($q) => $q->whereKeyNot($active->id))
                ->with('starter', 'closer', 'logins.user', 'extensions.user')->latest('starts_at')->get(),
            'today'   => today(),
        ]);
    }

    public function store(Request $request, SchoolClass $schoolClass)
    {
        $user = $request->user();
        abort_unless($this->mayManage($user, $schoolClass), 403);
        $data = $request->validate([
            'reason'   => ['required', 'string', 'max:200'],
            'duration' => ['required', Rule::in(array_merge(array_map('strval', ClassSession::MINUTES), ['days']))],
            'date'     => ['required_if:duration,days', 'nullable', 'date_format:Y-m-d',
                'after_or_equal:' . today()->toDateString(), 'before_or_equal:' . today()->addDays(ClassSession::MAX_DAYS)->toDateString()],
        ], [
            'reason.required' => __('Bitte gib einen Grund an, z. B. „Hausaufgaben“.'),
            'date.required_if' => __('Bitte wähle den Tag, bis zu dem die Anmeldung offen ist.'),
            'date.before_or_equal' => __('Höchstens :n Tage im Voraus.', ['n' => ClassSession::MAX_DAYS]),
            'date.after_or_equal' => __('Der Tag darf nicht in der Vergangenheit liegen.'),
        ]);
        if (ClassSession::activeFor($schoolClass->id)) {
            return back()->withErrors(['reason' => __('Für diese Klasse läuft schon eine Sitzung.')]);
        }

        $days = $data['duration'] === 'days';
        $end = $days ? ClassSession::endOnDay($data['date']) : now()->addMinutes((int) $data['duration']);
        if ($end->lte(now())) {
            return back()->withInput()->withErrors(['date' => __('Heute ist es schon nach :uhr Uhr — bitte einen späteren Tag wählen.', ['uhr' => ClassSession::END_TIME])]);
        }
        // Jede Sitzung hat einen 6-stelligen Code — für Kinder ersetzt er den zweiten Faktor
        // Every session has a 6-digit code — for children it replaces the second factor
        $code = ClassSession::newCode();

        ClassSession::create([
            'school_class_id' => $schoolClass->id,
            'started_by'      => $user->id,
            'reason'          => trim($data['reason']),
            'starts_at'       => now(),
            'ends_at'         => $end,
            'code'            => Crypt::encryptString($code),
        ]);

        return redirect()->route('classes.sessions', $schoolClass)->with('ok',
            __('Anmeldung für :klasse freigeschaltet bis :ende. Sitzungscode: :code', ['klasse' => $schoolClass->name, 'ende' => $end->format('d.m.Y H:i'), 'code' => $code]));
    }

    /**
     * Laufende Sitzung verlängern (Code bleibt, angemeldete Kinder bleiben angemeldet)
     *
     * Extend a running session (code stays, signed-in children stay signed in)
     */
    public function extend(Request $request, SchoolClass $schoolClass, ClassSession $session)
    {
        $user = $request->user();
        abort_unless($this->mayManage($user, $schoolClass) && $session->school_class_id === $schoolClass->id, 403);
        $data = $request->validate([
            'extend_by' => ['required', Rule::in(array_merge(array_map('strval', ClassSession::EXTEND_MINUTES), ['days']))],
            'date'      => ['required_if:extend_by,days', 'nullable', 'date_format:Y-m-d',
                'after_or_equal:' . today()->toDateString(), 'before_or_equal:' . today()->addDays(ClassSession::MAX_DAYS)->toDateString()],
        ], [
            'date.required_if' => __('Bitte wähle den Tag, bis zu dem die Anmeldung offen ist.'),
            'date.before_or_equal' => __('Höchstens :n Tage im Voraus.', ['n' => ClassSession::MAX_DAYS]),
        ]);
        if (! $session->isActive()) {
            return back()->withErrors(['extend_by' => __('Diese Sitzung läuft nicht mehr.')]);
        }

        $old = $session->ends_at->copy();
        $new = $data['extend_by'] === 'days'
            ? ClassSession::endOnDay($data['date'])
            : $old->copy()->addMinutes((int) $data['extend_by']);
        $new = $new->min(ClassSession::latestEnd());
        if ($new->lte($old)) {
            return back()->withErrors(['extend_by' => __('Die Sitzung läuft schon bis :ende — bitte einen späteren Zeitpunkt wählen.', ['ende' => $old->format('d.m. H:i')])]);
        }

        $session->update(['ends_at' => $new]);
        $session->extensions()->create(['user_id' => $user->id, 'old_end' => $old, 'new_end' => $new]);

        return redirect()->route('classes.sessions', $schoolClass)->with('ok', __('Sitzung verlängert bis :ende. Der Code bleibt gleich.', ['ende' => $new->format('d.m.Y H:i')]));
    }

    /**
     * Vorzeitig beenden — Grund ist Pflicht und wird dokumentiert; angemeldete Kinder fliegen raus
     *
     * End early — a reason is mandatory and is documented; signed-in children are signed out
     */
    public function close(Request $request, SchoolClass $schoolClass, ClassSession $session)
    {
        $user = $request->user();
        abort_unless($this->mayManage($user, $schoolClass) && $session->school_class_id === $schoolClass->id, 403);
        $data = $request->validate(['close_reason' => ['required', 'string', 'max:200']], [
            'close_reason.required' => __('Bitte gib an, warum die Sitzung beendet wird.'),
        ]);
        if (! $session->isActive()) {
            return back()->withErrors(['close_reason' => __('Diese Sitzung läuft nicht mehr.')]);
        }
        $session->update(['closed_at' => now(), 'closed_by' => $user->id, 'close_reason' => trim($data['close_reason'])]);

        return redirect()->route('classes.sessions', $schoolClass)->with('ok', __('Sitzung beendet. Die Kinder der Klasse :klasse sind abgemeldet.', ['klasse' => $schoolClass->name]));
    }
}

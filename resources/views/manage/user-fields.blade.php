{{-- Rolle: Lehrkraft oder Schüler; „Admin“ ist ein Zusatz-Haken für Lehrkräfte (intern Rolle admin)

     Role: teacher or pupil; “Admin” is an extra tick box for teachers (internally role admin) --}}
@php($roles = $actor->isAdmin()
    ? [\App\Models\User::TEACHER => \App\Models\User::ROLES['teacher'], \App\Models\User::PUPIL => \App\Models\User::ROLES['pupil']]
    : [\App\Models\User::PUPIL => \App\Models\User::ROLES['pupil']])
@php($currentRole = old('role', $user?->role === 'admin' ? 'teacher' : ($user?->role ?? 'pupil')))
@php($isAdmin = (bool) old('admin', $user?->isAdmin()))
<div class="row">
    <div><label>{{ __('Vorname') }}</label><input type="text" name="first_name" value="{{ old('first_name', $user?->first_name) }}" required maxlength="60"></div>
    <div><label>{{ __('Nachname') }}</label><input type="text" name="last_name" value="{{ old('last_name', $user?->last_name) }}" maxlength="60"></div>
</div>
<div class="row">
    <div><label>{{ __('Benutzername') }}</label><input type="text" name="username" value="{{ old('username', $user?->username) }}" maxlength="40"
            pattern="[a-z0-9._\-]+" autocapitalize="none" placeholder="{{ $user ? '' : __('leer = automatisch') }}"></div>
    <div><label>{{ __('Rolle') }}</label>
        <select name="role" onchange="this.form.querySelector('.teacher-only')?.toggleAttribute('hidden', this.value === 'pupil')">
            @foreach($roles as $k => $v)<option value="{{ $k }}" @selected($currentRole === $k)>{{ __($v) }}</option>@endforeach
        </select></div>
    <div><label>{{ __('Klasse') }}</label>
        <select name="school_class_id"><option value="">—</option>
            @foreach($classes as $c)<option value="{{ $c->id }}" @selected(old('school_class_id', $user?->school_class_id) == $c->id)>{{ $c->name }}</option>@endforeach
        </select></div>
</div>
@if($actor->isAdmin())
    <div class="teacher-only" @if($currentRole !== 'teacher') hidden @endif>
        <label class="check admin-tick"><input type="checkbox" name="admin" value="1" @checked($isAdmin)>
            <span><b>🔑 {{ __('Zusätzlich Admin') }}</b>
            <small>{{ __('Darf Benutzer, Klassen, Fächer und Einstellungen pflegen. Bücher sieht auch ein Admin nur in den Klassen, in denen er unterrichtet.') }}</small></span></label>
        <label for="mfa_policy">{{ __('Zwei-Faktor-Anmeldung (Ausnahme für diese Person)') }}</label>
        <select id="mfa_policy" name="mfa_policy" style="max-width:320px">
            <option value="" @selected(! old('mfa_policy', $user?->mfa_policy))>{{ __('wie Gruppe') }}</option>
            @foreach(\App\Support\Mfa::LEVELS as $level => $levelLabel)
                <option value="{{ $level }}" @selected(old('mfa_policy', $user?->mfa_policy) === $level)>{{ __($levelLabel) }}</option>
            @endforeach
        </select>
        <label>{{ __('Unterrichtet in diesen Klassen') }}</label>
        <p class="hint" style="margin-top:0">{{ __('Nur hier gibt es Zugriff auf Bücher: Lehrkräfte und Admins sehen ausschließlich die Bücher der Klassen, in denen sie unterrichten.') }}</p>
        <div class="row">
            @php($led = old('led_classes', $user?->ledClasses->pluck('id')->all() ?? []))
            @foreach($classes as $c)
                <label class="check auto"><input type="checkbox" name="led_classes[]" value="{{ $c->id }}" @checked(in_array($c->id, $led))> {{ $c->name }}</label>
            @endforeach
        </div>
    </div>
@endif
@if(! $user || $user->isLocal())
<div class="row">
    <div><label>{{ $user ? __('Neues Passwort (leer = unverändert)') : __('Passwort (leer = automatisch)') }}</label>
        <input type="text" name="password" autocomplete="new-password" maxlength="200"></div>
</div>
<label class="check"><input type="checkbox" name="card" value="1" @checked(old('card', $user?->hasCard()))>
    {{ __('Anmeldekarte hinterlegen (Passwort verschlüsselt speichern, Karte jederzeit neu druckbar)') }}</label>
<p class="hint">{{ __('Neue Lehrkräfte und Admins müssen ihr Passwort bei der ersten Anmeldung ändern.') }}</p>
@endif

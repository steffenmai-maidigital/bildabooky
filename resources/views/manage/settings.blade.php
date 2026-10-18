@extends('layouts.app')
@section('title', __('Einstellungen'))
@php($B = \App\Support\Branding::class)
@section('content')
    <h1>🎨 {{ __('Einstellungen') }}</h1>

    <form method="post" action="{{ route('settings.update') }}" id="settings">
        @csrf @method('put')

        <div class="panel">
            <h2 style="margin-top:0">1 · 🏫 {{ __('Schule') }}</h2>
            <label for="brand_company_name">{{ __('Name der Schule') }}</label>
            <input type="text" id="brand_company_name" name="brand_company_name" maxlength="80" required value="{{ old('brand_company_name', $B::school()) }}">
            <label for="brand_footer_text">{{ __('Text in der Fußzeile (freiwillig)') }}</label>
            <input type="text" id="brand_footer_text" name="brand_footer_text" maxlength="200" value="{{ old('brand_footer_text', $B::footerText()) }}"
                   placeholder="{{ __('z. B. Grundschule am Park · Musterstraße 1 · 12345 Musterstadt') }}">
        </div>

        <div class="panel">
            <h2 style="margin-top:0">2 · 🌐 {{ __('Domain') }}</h2>
            <label for="domain">{{ __('Öffentliche Adresse') }}</label>
            <input type="text" id="domain" name="domain" maxlength="200" value="{{ old('domain', $B::domain()) }}"
                   placeholder="https://buecher.meine-schule.de" autocapitalize="none" spellcheck="false">
            <p class="hint">{{ __('Unter dieser Adresse erreichen Kinder und Lehrkräfte BilDaBooky. Sie steht auf den Anmeldekarten und in allen Links. Leer lassen, um die Einstellung aus der Compose-Datei (APP_URL) zu verwenden.') }}</p>
            <p class="hint">{{ __('Für Kamera und Mikrofon muss die Adresse mit https:// beginnen.') }}</p>
            @if($errors->has('domain') && old('domain'))
                <label class="check"><input type="checkbox" name="domain_confirmed" value="1"> {{ __('Ja, die Adresse stimmt. Danach öffne ich BilDaBooky unter dieser Adresse.') }}</label>
            @endif
            <p class="hint"><b>{{ __('Erst eintragen, wenn BilDaBooky unter dieser Adresse schon erreichbar ist.') }}</b>
                {{ __('Am sichersten: BilDaBooky zuerst über die neue Adresse öffnen und sie dann hier eintragen. Sonst laden die Seiten von einer Adresse, die nicht antwortet, und man kommt nicht mehr hinein.') }}</p>
            <p class="hint">{{ __('Falls du dich ausgesperrt hast:') }} <code>docker exec bildabooky php artisan bildabooky:domain-leeren</code></p>
        </div>

        <div class="panel">
            <h2 style="margin-top:0">3 · 🎨 {{ __('Farben') }}</h2>
            <div class="colour-choice-list">
                @foreach(['brand_accent_color' => __('Akzentfarbe (Knöpfe, Hervorhebungen)'), 'brand_header_color' => __('Kopfzeile'), 'brand_background_color' => __('Hintergrund')] as $k => $title)
                    <label class="colour-choice">
                        <input type="color" name="{{ $k }}" value="{{ old($k, $B::colour($k)) }}" data-var="{{ $k }}">
                        <span>{{ $title }}<small>{{ __('Standard') }}: {{ $defaults[$k] }}</small></span>
                    </label>
                @endforeach
            </div>
            <div class="brand-preview" id="preview">
                <div class="bp-header"><span class="bp-logo"><img src="{{ $B::logoUrl() }}" alt=""></span><b>{{ $B::school() }}</b><span class="bp-nav">{{ __('Bibliothek') }}</span></div>
                <div class="bp-content"><span class="bp-button">{{ __('Neues Buch') }}</span><span class="bp-button second">{{ __('Lesen') }}</span></div>
            </div>
            <label class="check"><input type="checkbox" name="reset_colours" value="1"> {{ __('Farben auf Standard zurücksetzen') }}</label>
            <p class="hint">{{ __('Die Schriftfarbe auf Knöpfen und Kopfzeile wird automatisch hell oder dunkel gewählt, damit alles lesbar bleibt.') }}</p>
        </div>

        <div class="panel">
            <h2 style="margin-top:0">4 · 📜 {{ __('Lizenz') }}</h2>
            <div class="row">
                <div><label for="license_company">{{ __('Schule (Lizenznehmer)') }}</label>
                    <input type="text" id="license_company" name="license_company" maxlength="150" value="{{ old('license_company', \App\Models\Setting::get('license_company')) }}" placeholder="{{ __('Grundschule am Park') }}"></div>
                <div><label for="license_key">{{ __('Lizenzschlüssel (20 Zeichen)') }}</label>
                    <input type="text" id="license_key" name="license_key" maxlength="20" value="{{ old('license_key', \App\Models\Setting::get('license_key')) }}" placeholder="1ABCD123427073100500" style="font-family:monospace" autocapitalize="characters" spellcheck="false"></div>
            </div>
            <p class="hint">{{ __('Schema: Stelle 1 = 1 Standard- / 2 Enterprise-Lizenz · Stellen 2–9 = Kundennummer (Buchstaben/Zahlen) · Stellen 10–15 = Support-Ende als JJMMTT (z. B. 270731) · Stellen 16–20 = Lizenzanzahl (99999 = unlimitiert). Nur schema-konforme Schlüssel werden gespeichert.') }}</p>
            <p class="hint">{{ __('Die Anzeige erfolgt im Footer unter „Hilfe“. Ohne Lizenz läuft BilDaBooky als Community Edition. Ein abgelaufener Support wird rot angezeigt, hat aber keine funktionalen Auswirkungen.') }}</p>
        </div>

        <div class="panel summer {{ $summer['enabled'] ? 'on' : '' }}">
            <h2 style="margin-top:0">6 · ☀️ {{ __('Sommerferienmodus') }}</h2>
            <p>{{ __('Räumt zum Schuljahresende auf: Am gewählten Tag werden ALLE Schülerkonten mit ihren Büchern, Fotos, Tonaufnahmen und Protokolleinträgen endgültig gelöscht. Lehrkräfte und Admins bleiben erhalten.') }}</p>
            <label class="check"><input type="checkbox" name="summer_enabled" value="1" @checked(old('summer_enabled', $summer['enabled']))> <b>{{ __('Sommerferienmodus einschalten') }}</b></label>
            <div class="row">
                <div style="max-width:260px"><label for="summer_date">{{ __('Datum der Durchführung') }}</label>
                    <input type="date" id="summer_date" name="summer_date" min="{{ today()->addDay()->toDateString() }}"
                           value="{{ old('summer_date', $summer['date']?->toDateString()) }}"></div>
            </div>
            <label class="check"><input type="checkbox" name="summer_class_books" value="1" @checked(old('summer_class_books', $summer['options']['class_books']))> {{ __('Auch Klassenbücher und Vorlagen löschen (sie enthalten Inhalte der Kinder)') }}</label>
            <label class="check"><input type="checkbox" name="summer_classes" value="1" @checked(old('summer_classes', $summer['options']['classes']))> {{ __('Auch die Klassen löschen — empfohlen: Das neue Schuljahr beginnt leer, Lehrkräfte bleiben und tragen sich in die neuen Klassen ein (dann werden auch Klassenbücher und Aufgaben gelöscht)') }}</label>
            <label class="check"><input type="checkbox" name="summer_yearly" value="1" @checked(old('summer_yearly', $summer['options']['yearly']))> {{ __('Jedes Jahr am selben Tag wiederholen') }}</label>

            @php($v = $summer['preview'])
            <div class="flash info" style="margin-top:.8rem">
                <b>{{ __('Würde jetzt gelöscht:') }}</b>
                {{ __(':kinder Schülerkonten, :buecher Bücher, :medien Fotos und Tonaufnahmen', ['kinder' => $v['pupils'], 'buecher' => $v['books'], 'medien' => $v['media']]) }}@if($v['classes']), {{ __(':n Klassen', ['n' => $v['classes']]) }}@endif.
            </div>
            <ul class="hint">
                <li>{{ __('Unmittelbar vorher wird automatisch eine Sicherung im Ordner „backups“ angelegt; sie wird wie alle Sicherungen nach der eingestellten Frist gelöscht.') }}</li>
                <li>{{ __('Drei Wochen vorher sehen alle einen Hinweis — die Kinder werden gebeten, ihre Bücher als Datei mit nach Hause zu nehmen.') }}</li>
                <li>{{ __('Die Löschung läuft in der Nacht des gewählten Tages (um 3 Uhr).') }}</li>
            </ul>
            @if(! $summer['enabled'] || $errors->has('summer_confirmed'))
                <label class="check warning"><input type="checkbox" name="summer_confirmed" value="1"> {{ __('Ich habe verstanden: Die Löschung ist endgültig und kann nicht rückgängig gemacht werden.') }}</label>
            @endif
            @if($l = $summer['last'])
                <p class="hint">{{ __('Zuletzt durchgeführt am :am: :kinder Schülerkonten und :buecher Bücher gelöscht. Sicherung: :datei', ['am' => \Illuminate\Support\Carbon::parse($l['at'])->format('d.m.Y H:i'), 'kinder' => $l['pupils'], 'buecher' => $l['books'], 'datei' => $l['backup'] ?? '—']) }}</p>
            @endif
        </div>

        <div class="panel">
            <h2 style="margin-top:0">7 · 🦊 {{ __('Fuchs-Helfer') }}</h2>
            <p>{{ __('Ein freundlicher Fuchs unten rechts begrüßt, lobt und gibt Tipps — passend für Kinder, Lehrkräfte und Admins.') }}</p>
            <label class="check"><input type="checkbox" name="fox_enabled" value="1" @checked(old('fox_enabled', \App\Models\Setting::get('fox_enabled', '1') === '1'))> <b>{{ __('Fuchs für alle anzeigen') }}</b></label>
            <p class="hint">{{ __('Ausgeschaltet ist er für niemanden sichtbar. Eingeschaltet kann ihn jede Person unter „Konto“ für sich selbst ausblenden.') }}</p>
        </div>


        <div class="panel">
            <h2 style="margin-top:0">8 · 🔓 {{ __('Anmeldung und Klassen') }}</h2>
            <label class="check"><input type="checkbox" name="session_required" value="1" @checked(old('session_required', \App\Support\SessionRequirement::required()))>
                <b>{{ __('Kinder können sich nur während einer Sitzung ihrer Klasse anmelden') }}</b></label>
            <label class="check"><input type="checkbox" name="teachers_can_join" value="1" @checked(old('teachers_can_join', \App\Http\Controllers\ClassController::joiningAllowed()))>
                <b>{{ __('Lehrkräfte dürfen sich selbst in Klassen eintragen') }}</b></label>
            <p class="hint">{{ __('Unter „Klassen“ sehen Lehrkräfte dann alle Klassen und können sich mit ihren Fächern eintragen — praktisch zum Schuljahresbeginn. Ausgeschaltet ordnet nur der Admin zu.') }}</p>
            <p class="hint">{{ __('Lehrkräfte schalten die Anmeldung je Klasse frei (60, 120, 180 Minuten oder bis zu 7 Tage, dann bis 17 Uhr). Die Kinder melden sich mit Passwort und dem 6-stelligen Sitzungscode an — das ersetzt bei ihnen die Zwei-Faktor-Anmeldung. Ausgeschaltet können sich Kinder jederzeit nur mit Passwort anmelden.') }}</p>

            <h3>🔐 {{ __('Zwei-Faktor-Anmeldung') }}</h3>
            <p class="hint" style="margin-top:0">{{ __('Zusätzlicher Code aus einer Authenticator-App für Lehrkräfte und Admins — je Gruppe: Pflicht (muss eingerichtet werden), wählbar (jeder kann) oder aus. Kinder haben nie einen zweiten Faktor. Ausnahmen je Person unter „Benutzer“.') }}</p>
            <div class="row">
                @foreach(\App\Support\Mfa::groups() as $mfaKey => [$mfaLabel])
                    <div style="max-width:260px">
                        <label for="mfa_{{ $mfaKey }}">{{ $mfaLabel }}</label>
                        <select id="mfa_{{ $mfaKey }}" name="mfa_{{ $mfaKey }}">
                            @foreach(\App\Support\Mfa::LEVELS as $level => $levelLabel)
                                <option value="{{ $level }}" @selected(old('mfa_' . $mfaKey, \App\Support\Mfa::level($mfaKey)) === $level)>{{ __($levelLabel) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>
            <label for="mfa_required_groups">{{ __('Gruppen im Schulverzeichnis mit Pflicht (eine je Zeile, DN oder Name)') }}</label>
            <textarea id="mfa_required_groups" name="mfa_required_groups" rows="2" placeholder="Schulleitung&#10;CN=IT-Admins,OU=Gruppen,DC=schule,DC=local">{{ old('mfa_required_groups', \App\Models\Setting::get('mfa_required_groups')) }}</textarea>
            <p class="hint">{{ __('Mitglieder dieser Gruppen müssen den zweiten Faktor einrichten. Wirkt ab der nächsten Anmeldung.') }}</p>
        </div>

        <div class="panel">
            <h2 style="margin-top:0">9 · 🏫 {{ __('Anmeldung über das Schulverzeichnis') }}</h2>
            <p>{{ __('Lehrkräfte und Admins können sich zusätzlich mit ihrem Schulkonto anmelden — Active Directory, Samba, Univention oder OpenLDAP. Kinder haben immer eigene Konten in BilDaBooky. Eigene Konten für Lehrkräfte funktionieren weiterhin.') }}</p>
            <label class="check"><input type="checkbox" name="ldap_enabled" value="1" @checked(old('ldap_enabled', $directory['enabled']))> <b>{{ __('Anmeldung über das Schulverzeichnis einschalten') }}</b></label>
            <div class="row">
                <div><label for="ldap_host">{{ __('Server (Hostname oder IP, mehrere mit Komma)') }}</label><input type="text" id="ldap_host" name="ldap_host" value="{{ old('ldap_host', $directory['host']) }}" placeholder="dc01.schule.local" autocapitalize="none" spellcheck="false"></div>
                <div style="max-width:140px"><label for="ldap_port">{{ __('Port') }}</label><input type="number" id="ldap_port" name="ldap_port" value="{{ old('ldap_port', $directory['port']) }}" min="1" max="65535"></div>
                <div style="max-width:260px"><label for="ldap_encryption">{{ __('Verschlüsselung') }}</label>
                    <select id="ldap_encryption" name="ldap_encryption">
                        @foreach(\App\Support\Directory::ENCRYPTION as $value => $title)
                            <option value="{{ $value }}" @selected(old('ldap_encryption', $directory['encryption']) === $value)>{{ __($title) }}</option>
                        @endforeach
                    </select></div>
            </div>
            <label class="check"><input type="checkbox" name="ldap_certificate" value="1" @checked(old('ldap_certificate', $directory['certificate']))> {{ __('Zertifikat des Servers prüfen (empfohlen; aus bei selbst signierten Zertifikaten im Testnetz)') }}</label>
            <label for="ldap_ca_certificate">{{ __('Eigene Zertifizierungsstelle (CA-Zertifikat im PEM-Format, optional)') }}</label>
            <textarea id="ldap_ca_certificate" name="ldap_ca_certificate" rows="4" spellcheck="false" autocapitalize="none" placeholder="-----BEGIN CERTIFICATE-----&#10;…&#10;-----END CERTIFICATE-----" style="font-family:monospace;font-size:.8rem">{{ old('ldap_ca_certificate', $directory['ca_certificate']) }}</textarea>
            <p class="hint">{{ __('Nötig, wenn das Verzeichnis ein Zertifikat der eigenen Firmen- oder Schul-CA verwendet. Dann die Prüfung eingeschaltet lassen, statt sie abzuschalten.') }}</p>
            <label for="ldap_base_dn">{{ __('Suchbasis (Base DN)') }}</label>
            <input type="text" id="ldap_base_dn" name="ldap_base_dn" value="{{ old('ldap_base_dn', $directory['base_dn']) }}" placeholder="DC=schule,DC=local" autocapitalize="none" spellcheck="false">
            <div class="row">
                <div><label for="ldap_bind_dn">{{ __('Servicekonto (DN oder Anmeldename, nur lesend)') }}</label><input type="text" id="ldap_bind_dn" name="ldap_bind_dn" value="{{ old('ldap_bind_dn', $directory['bind_dn']) }}" placeholder="CN=svc-bildabooky,OU=Service,DC=schule,DC=local" autocomplete="off"></div>
                <div><label for="ldap_bind_password">{{ __('Passwort des Servicekontos') }} <span class="hint">({{ __('leer lassen = unverändert') }})</span></label><input type="password" id="ldap_bind_password" name="ldap_bind_password" value="" autocomplete="new-password"></div>
            </div>
            <div class="row">
                <div><label for="ldap_group_teacher">{{ __('Gruppe der Lehrkräfte (DN oder Name, leer = alle dürfen)') }}</label><input type="text" id="ldap_group_teacher" name="ldap_group_teacher" value="{{ old('ldap_group_teacher', \App\Models\Setting::get('ldap_group_teacher')) }}" placeholder="Lehrkraefte"></div>
                <div><label for="ldap_group_admin">{{ __('Gruppe der Admins (DN oder Name)') }}</label><input type="text" id="ldap_group_admin" name="ldap_group_admin" value="{{ old('ldap_group_admin', \App\Models\Setting::get('ldap_group_admin')) }}" placeholder="BilDaBooky-Admins"></div>
            </div>
            <details style="margin-top:.6rem">
                <summary style="cursor:pointer">{{ __('Weitere Angaben (meist nicht nötig)') }}</summary>
                <div class="row">
                    <div><label for="ldap_user_attribute">{{ __('Attribut mit dem Benutzernamen') }}</label><input type="text" id="ldap_user_attribute" name="ldap_user_attribute" value="{{ old('ldap_user_attribute', $directory['user_attribute']) }}" placeholder="sAMAccountName"><p class="hint">{{ __('Active Directory: sAMAccountName · OpenLDAP/Univention: uid') }}</p></div>
                    <div><label for="ldap_user_filter">{{ __('Benutzerfilter (LDAP-Filter)') }}</label><input type="text" id="ldap_user_filter" name="ldap_user_filter" value="{{ old('ldap_user_filter', $directory['user_filter']) }}" placeholder="(objectClass=person)"><p class="hint">{{ __('Schränkt ein, wer sich anmelden darf, z. B. nur aktive Konten einer OU.') }}</p></div>
                </div>
                <div class="row">
                    <div><label for="ldap_bind_template">{{ __('Ohne Servicekonto: Anmeldename nach Vorlage') }}</label><input type="text" id="ldap_bind_template" name="ldap_bind_template" value="{{ old('ldap_bind_template', $directory['bind_template']) }}" placeholder="%s@schule.local"><p class="hint">{{ __('%s wird durch den eingegebenen Benutzernamen ersetzt, z. B. %s@schule.local oder uid=%s,ou=people,dc=schule,dc=local.') }}</p></div>
                    <div style="max-width:180px"><label for="ldap_timeout">{{ __('Zeitlimit (Sekunden)') }}</label><input type="number" id="ldap_timeout" name="ldap_timeout" value="{{ old('ldap_timeout', $directory['timeout']) }}" min="1" max="60"></div>
                </div>
            </details>
            <p class="hint">{{ __('Beim ersten Anmelden entsteht das Konto automatisch; Klassen und Fächer ordnest du danach unter „Benutzer“ zu. Nach dem Speichern unten „Verbindung testen“ antippen.') }}</p>
        </div>

        <div class="panel legal-settings">
            <h2 style="margin-top:0">10 · ⚖️ {{ __('Impressum und Datenschutz') }}</h2>
            <p>{{ __('Eingeschaltete Seiten sind ohne Anmeldung erreichbar und stehen auf der Anmeldeseite und unten auf jeder Seite.') }}</p>
            @foreach(\App\Support\LegalPages::TYPES as $type)
                @php($R = \App\Support\LegalPages::class)
                @php($typeChoice = old($type . '_type', \App\Models\Setting::get($type . '_type', 'text')))
                <fieldset class="legal" data-type="{{ $type }}">
                    <legend>{{ $type === 'imprint' ? '⚖️' : '🔒' }} {{ $R::title($type) }}
                        @if($R::enabled($type)) · <a href="{{ $R::url($type) }}" target="_blank" rel="noopener">👀 {{ __('Ansehen') }}</a>@endif</legend>
                    <label class="check"><input type="checkbox" name="{{ $type }}_enabled" value="1" @checked(old($type . '_enabled', \App\Models\Setting::get($type . '_enabled', '0') === '1'))>
                        <b>{{ __(':seite anzeigen', ['seite' => $R::title($type)]) }}</b></label>
                    <div class="legal-choice">
                        <label class="check"><input type="radio" name="{{ $type }}_type" value="text" @checked($typeChoice !== 'link')> {{ __('Text hier eingeben') }}</label>
                        <label class="check"><input type="radio" name="{{ $type }}_type" value="link" @checked($typeChoice === 'link')> {{ __('Auf eine vorhandene Seite verlinken (z. B. Schulhomepage)') }}</label>
                    </div>
                    <div class="legal-text">
                        <textarea name="{{ $type }}_text" id="{{ $type }}_text" rows="10" maxlength="{{ $R::MAX }}"
                                  placeholder="{{ $type === 'imprint' ? __('Name und Anschrift der Schule, vertreten durch …, Telefon, E-Mail, Schulträger …') : __('Wer verantwortlich ist, welche Daten verarbeitet werden, wo sie liegen, wie lange, Rechte der Betroffenen …') }}">{{ old($type . '_text', $R::text($type)) }}</textarea>
                        <p class="hint">{{ __('Einfacher Text: Leerzeile = neuer Absatz, eine Zeile mit „# “ davor = Überschrift. Internet- und E-Mail-Adressen werden automatisch zu Links.') }}</p>
                        @if($type === 'privacy')
                            <button type="button" class="small" id="privacy-template" data-template="{{ $R::privacyTemplate() }}">📝 {{ __('Vorlage für BilDaBooky einfügen') }}</button>
                            <p class="hint">{{ __('Die Vorlage beschreibt, was BilDaBooky tatsächlich speichert. Angaben in [eckigen Klammern] ersetzen und vor dem Einschalten von der Schulleitung bzw. dem Datenschutz prüfen lassen.') }}</p>
                        @endif
                    </div>
                    <div class="legal-url">
                        <label for="{{ $type }}_url">{{ __('Adresse der Seite') }}</label>
                        <input type="url" id="{{ $type }}_url" name="{{ $type }}_url" maxlength="500" value="{{ old($type . '_url', \App\Models\Setting::get($type . '_url')) }}"
                               placeholder="https://www.meine-schule.de/{{ $type === 'imprint' ? 'impressum' : 'datenschutz' }}" autocapitalize="none" spellcheck="false">
                    </div>
                </fieldset>
            @endforeach
        </div>

        <div class="actions" style="margin-bottom:1.2rem"><button class="primary">💾 {{ __('Speichern') }}</button></div>
    </form>

    <div class="panel">
        <h2 style="margin-top:0">🏫 {{ __('Verbindung zum Schulverzeichnis testen') }}</h2>
        <form method="post" action="{{ route('settings.test-directory') }}">
            @csrf
            <button class="auto">🔌 {{ __('Verbindung testen') }}</button>
        </form>
        <p class="hint">{{ __('Prüft mit den gespeicherten Angaben aus Abschnitt 9: Server erreichbar, Verschlüsselung, Servicekonto, Suchbasis lesbar.') }}</p>
    </div>

    <div class="panel">
        <h2 style="margin-top:0">5 · 🖼️ {{ __('Logo') }}</h2>
        <div class="row" style="align-items:center">
            <div class="auto logo-preview"><img src="{{ $B::logoUrl() }}" alt="{{ __('Logo') }}"></div>
            <div>
                <form method="post" action="{{ route('settings.logo') }}" enctype="multipart/form-data">
                    @csrf
                    <label for="logo">{{ __('Neues Logo (PNG, JPG, WebP oder SVG, höchstens 2 MB)') }}</label>
                    <div class="row"><input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" required>
                        <button class="auto">📤 {{ __('Hochladen') }}</button></div>
                </form>
                @if($B::hasLogo())
                    <form method="post" action="{{ route('settings.logo.delete') }}" style="margin-top:.6rem">
                        @csrf @method('delete')<button class="small danger">🗑️ {{ __('Eigenes Logo entfernen') }}</button></form>
                @endif
                <p class="hint">{{ __('Am besten quadratisch, mindestens 256 × 256 Pixel. Das Logo erscheint in der Kopfzeile, auf der Anmeldeseite und als Symbol im Browser-Tab.') }}</p>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
<script>
    // Vorschau: Farben sofort zeigen (gespeichert wird erst mit „Speichern“)
    // Preview: show colours at once (saved only with “Save”)
    (function () {
        const v = document.getElementById('preview');
        const ink = (hex) => {
            const c = [1, 3, 5].map((i) => parseInt(hex.substr(i, 2), 16) / 255).map((x) => x <= 0.03928 ? x / 12.92 : ((x + 0.055) / 1.055) ** 2.4);
            const l = 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
            return (1.05 / (l + 0.05)) >= ((l + 0.05) / 0.0819) ? '#ffffff' : '#26324a';
        };
        const apply = () => {
            const w = {};
            document.querySelectorAll('[data-var]').forEach((i) => { w[i.dataset.var] = i.value; });
            v.style.setProperty('--bp-accent', w.brand_accent_color);
            v.style.setProperty('--bp-accent-ink', ink(w.brand_accent_color));
            v.style.setProperty('--bp-header', w.brand_header_color);
            v.style.setProperty('--bp-header-ink', ink(w.brand_header_color));
            v.style.setProperty('--bp-background', w.brand_background_color);
        };
        document.querySelectorAll('[data-var]').forEach((i) => i.addEventListener('input', apply));
        apply();
    })();

    // Impressum/Datenschutz: je nach Wahl Textfeld oder Adresse zeigen; Vorlage einfügen
    // Imprint/privacy: show text field or address depending on the choice; insert template
    (function () {
        document.querySelectorAll('fieldset.legal').forEach((fs) => {
            const show = () => {
                const link = fs.querySelector('input[type=radio][value=link]').checked;
                fs.querySelector('.legal-text').hidden = link;
                fs.querySelector('.legal-url').hidden = !link;
            };
            fs.querySelectorAll('input[type=radio]').forEach((r) => r.addEventListener('change', show));
            show();
        });
        const button = document.getElementById('privacy-template');
        if (button) button.addEventListener('click', () => {
            const field = document.getElementById('privacy_text');
            if (field.value.trim() && !confirm(@json(__('Den vorhandenen Text durch die Vorlage ersetzen?')))) return;
            field.value = button.dataset.template;
            field.focus();
        });
    })();
</script>
@endpush

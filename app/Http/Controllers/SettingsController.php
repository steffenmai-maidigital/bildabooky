<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\Branding;
use App\Support\License;
use App\Support\LegalPages;
use App\Support\SummerHoliday;
use App\Support\Directory;
use Illuminate\Http\Request;

/** Admin: Schule, Domain, Farben und Logo anpassen / Admin: customise school, domain, colours and logo */
class SettingsController extends Controller
{
    public function show()
    {
        return view('manage.settings', [
            'defaults'  => Branding::DEFAULTS,
            'directory' => Directory::settings(),
            'summer'    => [
                'enabled' => SummerHoliday::enabled(),
                'date'    => SummerHoliday::date(),
                'options' => SummerHoliday::options(),
                'preview' => SummerHoliday::preview(),
                'last'    => SummerHoliday::last(),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $hex = ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'];
        $data = $request->validate([
            'brand_company_name'     => ['required', 'string', 'max:80'],
            'brand_footer_text'      => ['nullable', 'string', 'max:200'],
            'domain'                 => ['nullable', 'string', 'max:200', 'regex:#^https?://[a-z0-9.-]+(:\d+)?/?$#i'],
            'brand_accent_color'     => $hex,
            'brand_header_color'     => $hex,
            'brand_background_color' => $hex,
            'license_company'        => ['nullable', 'string', 'max:150'],
            'license_key'            => ['nullable', 'string', 'max:20'],
            'summer_date'            => ['nullable', 'date_format:Y-m-d'],
            'imprint_type'           => ['nullable', 'in:text,link'],
            'imprint_text'           => ['nullable', 'string', 'max:' . LegalPages::MAX],
            'imprint_url'            => ['nullable', 'string', 'max:500', 'regex:#^https?://[^\s<>"]+$#i'],
            'privacy_type'           => ['nullable', 'in:text,link'],
            'privacy_text'           => ['nullable', 'string', 'max:' . LegalPages::MAX],
            'privacy_url'            => ['nullable', 'string', 'max:500', 'regex:#^https?://[^\s<>"]+$#i'],
            'ldap_group_admin'       => ['nullable', 'string', 'max:500'],
            'ldap_group_teacher'     => ['nullable', 'string', 'max:500'],
            'mfa_admin'              => ['required', 'in:required,optional,off'],
            'mfa_teacher'            => ['required', 'in:required,optional,off'],
            'mfa_required_groups'    => ['nullable', 'string', 'max:4000'],
        ] + Directory::rules(), [
            'ldap_user_filter.regex' => __('Der Benutzerfilter muss ein vollständiger LDAP-Filter in Klammern sein, z. B. (objectClass=person).'),
            'domain.regex'           => __('Bitte nur die Adresse angeben, z. B. https://buecher.meine-schule.de'),
            'imprint_url.regex'      => __('Bitte eine vollständige Adresse angeben, z. B. https://www.meine-schule.de/impressum'),
            'privacy_url.regex'      => __('Bitte eine vollständige Adresse angeben, z. B. https://www.meine-schule.de/impressum'),
        ]);

        // Lizenzschlüssel nur nach Schema akzeptieren (1|2 + 8x Kundennr. +
        // JJMMTT Supportende + 5-stellige Anzahl) — reine Anzeige, wie in CrewGate
        // Accept licence keys only according to the scheme (1|2 + 8x customer no. +
        // YYMMDD end of support + 5-digit count) — display only, as in CrewGate
        if (filled($data['license_key'] ?? null) && ! License::parse($data['license_key'])) {
            return back()->withInput()->withErrors([
                'license_key' => __('Der Lizenzschlüssel entspricht nicht dem Schema: Stelle 1 = 1 (Standard) oder 2 (Enterprise), Stellen 2–9 Kundennummer (Buchstaben/Zahlen), Stellen 10–15 Support-Ende als JJMMTT, Stellen 16–20 Lizenzanzahl.'),
            ]);
        }

        // Sommerferienmodus: Datum Pflicht, nicht in der Vergangenheit;
        // beim Einschalten muss die endgültige Löschung bestätigt werden
        // Summer holiday mode: date mandatory, not in the past;
        // when switching on, the permanent deletion must be confirmed
        $summerOn = $request->boolean('summer_enabled');
        if ($summerOn) {
            if (empty($data['summer_date'])) {
                return back()->withInput()->withErrors(['summer_date' => __('Bitte das Datum der Durchführung angeben.')]);
            }
            $new = ! SummerHoliday::enabled() || SummerHoliday::date()?->toDateString() !== $data['summer_date'];
            // frühestens morgen — damit ein versehentliches „heute“ nie sofort löscht
            // tomorrow at the earliest — so that an accidental “today” never deletes immediately
            if ($new && \Illuminate\Support\Carbon::parse($data['summer_date'])->lte(today())) {
                return back()->withInput()->withErrors(['summer_date' => __('Bitte ein Datum ab morgen wählen.')]);
            }
            if ($new && ! $request->boolean('summer_confirmed')) {
                return back()->withInput()->withErrors(['summer_confirmed' => __('Bitte bestätige, dass am gewählten Tag alle Schülerkonten mit ihren Büchern endgültig gelöscht werden.')]);
            }
        }

        // Schulverzeichnis einschalten geht nur mit Server und Suchbasis
        // Switching on the school directory only works with a server and search base
        if ($request->boolean('ldap_enabled') && (blank($data['ldap_host'] ?? null) || blank($data['ldap_base_dn'] ?? null))) {
            return back()->withInput()->withErrors(['ldap_host' => __('Zum Einschalten der Anmeldung über das Schulverzeichnis bitte Server und Suchbasis eintragen.')]);
        }

        // Schutz vor dem Aussperren: eine neue Domain muss man bereits benutzen
        // oder ausdrücklich bestätigen (Notausgang: php artisan bildabooky:clear-domain)
        // Protection against locking yourself out: a new domain must already be in use
        // or be confirmed explicitly (emergency exit: php artisan bildabooky:clear-domain)
        $domain = filled($data['domain'] ?? null) ? rtrim(strtolower($data['domain']), '/') : null;
        if ($domain && $domain !== Branding::domain()
            && parse_url($domain, PHP_URL_HOST) !== $request->getHost()
            && ! $request->boolean('domain_confirmed')) {
            return back()->withInput()->withErrors(['domain' => __('Du rufst BilDaBooky gerade nicht über :domain auf. Bitte bestätige, dass die Adresse stimmt.', ['domain' => $domain])]);
        }

        // Impressum und Datenschutz: einschalten geht nur mit Inhalt
        // Imprint and privacy policy: can only be switched on with content
        foreach (LegalPages::TYPES as $type) {
            $typeChoice = ($data[$type . '_type'] ?? 'text') === 'link' ? 'link' : 'text';
            $field = $type . ($typeChoice === 'link' ? '_url' : '_text');
            $content = trim((string) ($data[$field] ?? ''));
            if ($request->boolean($type . '_enabled') && $content === '') {
                return back()->withInput()->withErrors([$field => $typeChoice === 'link'
                    ? __(':seite: Bitte die Adresse der Seite angeben oder die Seite ausschalten.', ['seite' => LegalPages::title($type)])
                    : __(':seite: Bitte einen Text eingeben oder die Seite ausschalten.', ['seite' => LegalPages::title($type)])]);
            }
        }

        if ($request->boolean('reset_colours')) {
            foreach (array_keys(Branding::DEFAULTS) as $k) {
                Setting::forget($k);
            }
        } else {
            foreach (array_keys(Branding::DEFAULTS) as $k) {
                $v = strtolower((string) ($data[$k] ?? ''));
                $v && $v !== Branding::DEFAULTS[$k] ? Setting::put($k, $v) : Setting::forget($k);
            }
        }
        Setting::put('brand_company_name', trim($data['brand_company_name']));
        Setting::put('brand_footer_text', trim((string) ($data['brand_footer_text'] ?? '')));
        Setting::put('license_company', trim((string) ($data['license_company'] ?? '')));
        Setting::put('license_key', trim((string) ($data['license_key'] ?? '')));
        $domain ? Setting::put('domain', $domain) : Setting::forget('domain');

        Setting::put('summer_enabled', $summerOn ? '1' : '0');
        if (! empty($data['summer_date'])) {
            Setting::put('summer_date', $data['summer_date']);
        }
        Setting::put('fox_enabled', $request->boolean('fox_enabled') ? '1' : '0');

        // Anmeldung über das Schulverzeichnis (Standard 09)
        // Sign-in via the school directory (Standard 09)
        Directory::save($request->only(Directory::KEYS) + ['ldap_enabled' => $request->boolean('ldap_enabled'), 'ldap_certificate' => $request->boolean('ldap_certificate')]);
        Setting::put('ldap_group_admin', trim((string) ($data['ldap_group_admin'] ?? '')));
        Setting::put('ldap_group_teacher', trim((string) ($data['ldap_group_teacher'] ?? '')));
        Setting::put('session_required', $request->boolean('session_required') ? '1' : '0');
        Setting::put('mfa_admin', $data['mfa_admin']);
        Setting::put('mfa_teacher', $data['mfa_teacher']);
        Setting::put('mfa_required_groups', trim((string) ($data['mfa_required_groups'] ?? '')));
        Setting::put('teachers_can_join', $request->boolean('teachers_can_join') ? '1' : '0');

        foreach (LegalPages::TYPES as $type) {
            Setting::put($type . '_enabled', $request->boolean($type . '_enabled') ? '1' : '0');
            Setting::put($type . '_type', ($data[$type . '_type'] ?? 'text') === 'link' ? 'link' : 'text');
            Setting::put($type . '_text', str_replace("\r", '', (string) ($data[$type . '_text'] ?? '')));
            Setting::put($type . '_url', trim((string) ($data[$type . '_url'] ?? '')));
        }

        foreach (['summer_class_books', 'summer_classes', 'summer_yearly'] as $k) {
            Setting::put($k, $request->boolean($k) ? '1' : '0');
        }

        $warning = null;
        $accent = Branding::colour('brand_accent_color');
        if (Branding::contrast($accent, Branding::colour('brand_background_color')) < 1.8) {
            $warning = __('Die Akzentfarbe hebt sich kaum vom Hintergrund ab. Knöpfe sind dann schlecht zu erkennen.');
        }

        return redirect()->route('settings')->with('ok', __('Einstellungen gespeichert.'))->with('notice', $warning);
    }

    /**
     * Verbindung zum Schulverzeichnis mit den gespeicherten Einstellungen prüfen
     *
     * Test the connection to the school directory with the saved settings
     */
    public function testLdap()
    {
        $k = Directory::settings();
        if ($k['host'] === '' || $k['base_dn'] === '') {
            return redirect()->route('settings')->withErrors(['ldap_host' => __('Bitte zuerst Server und Suchbasis speichern.')]);
        }
        try {
            $result = (new Directory($k))->test();
        } catch (\Throwable $e) {
            return redirect()->route('settings')->withErrors(['ldap_host' => __('Verbindung fehlgeschlagen: :fehler', ['fehler' => $e->getMessage()])]);
        }

        return redirect()->route('settings')->with('ok', $result['mode'] === 'template'
            ? __('Verbindung zum Verzeichnis in Ordnung: Server erreichbar, Verschlüsselung und Zertifikat passen (:ms ms). Ohne Servicekonto lässt sich die Suchbasis erst bei einer Anmeldung prüfen – bitte einmal mit einem Verzeichniskonto anmelden.', ['ms' => $result['milliseconds']])
            : __('Verbindung zum Schulverzeichnis in Ordnung: Servicekonto angemeldet, Suchbasis lesbar (:n Einträge geprüft, :ms ms).', [
            'n' => $result['entries'], 'ms' => $result['milliseconds'],
        ]));
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => ['required', 'file', 'max:2048', 'mimes:png,jpg,jpeg,webp,svg'],
        ]);
        $file = $request->file('logo');
        $ext = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/svg+xml' => 'svg', 'image/svg' => 'svg']
            [$file->getMimeType()] ?? null;
        if (! $ext) {
            return back()->withErrors(['logo' => __('Bitte ein Bild im Format PNG, JPG, WebP oder SVG hochladen.')]);
        }

        $dir = Branding::logoDirectory();
        @mkdir($dir, 0775, true);
        foreach (glob($dir . '/logo.*') ?: [] as $old) {
            @unlink($old);
        }
        if (! @move_uploaded_file($file->getRealPath(), "$dir/logo.$ext") && ! @copy($file->getRealPath(), "$dir/logo.$ext")) {
            return back()->withErrors(['logo' => __('Das Logo konnte nicht gespeichert werden.')]);
        }
        Setting::put('brand_logo_path', "logo.$ext");
        Setting::put('brand_logo_version', (string) time());

        return redirect()->route('settings')->with('ok', __('Logo gespeichert.'));
    }

    public function deleteLogo()
    {
        foreach (glob(Branding::logoDirectory() . '/logo.*') ?: [] as $old) {
            @unlink($old);
        }
        Setting::forget('brand_logo_path');
        return redirect()->route('settings')->with('ok', __('Logo entfernt.'));
    }

    /**
     * Logo ausliefern — öffentlich, weil es schon auf der Anmeldeseite erscheint
     *
     * Deliver the logo — public, because it already appears on the sign-in page
     */
    public function logo()
    {
        abort_unless(Branding::hasLogo(), 404);
        $path = Branding::logoDirectory() . '/' . Setting::get('brand_logo_path');
        $mime = str_ends_with($path, '.svg') ? 'image/svg+xml' : (mime_content_type($path) ?: 'image/png');
        $response = response()->file($path, [
            'Content-Type'            => $mime,
            'X-Content-Type-Options'  => 'nosniff',
            // SVG darf beim direkten Aufruf keine Skripte ausführen
            // SVG must not run scripts when opened directly
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
        ]);
        $response->setPublic();
        $response->setMaxAge(31536000);
        return $response;
    }
}

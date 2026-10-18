<?php

namespace App\Providers;

use App\Support\Branding;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Öffentliche Adresse für alle erzeugten Links (Anmeldekarten, Weiterleitungen,
        // Medien, CSS/JS): zuerst die vom Admin eingetragene Domain, sonst APP_URL —
        // aber nur, wenn APP_URL mit https:// beginnt. Hinter Tunnel/Proxy-Ketten, die
        // intern „http“ melden (z. B. ein vorgeschalteter Proxy vor Nginx Proxy Manager), entstehen
        // so trotzdem https-Links. Mit http://… (Standard: http://localhost:7290) bleibt
        // es bei der Adresse aus der Anfrage.
        // Public address for all generated links (login cards, redirects,
        // media, CSS/JS): first the domain entered by the admin, otherwise APP_URL —
        // but only if APP_URL starts with https://. Behind tunnel/proxy chains that
        // report “http” internally (e.g. an upstream proxy in front of Nginx Proxy Manager), this
        // still produces https links. With http://… (default: http://localhost:7290) the
        // address from the request is used.
        try {
            $domain = Branding::domain();
        } catch (Throwable) {
            $domain = null;   // Datenbank noch nicht bereit (erste Migration) / database not ready (first migration)
        }
        $appUrl = rtrim((string) config('app.url'), '/');
        $basis = $domain ?: (str_starts_with($appUrl, 'https://') ? $appUrl : null);
        if ($basis) {
            URL::forceRootUrl($basis);
            if (str_starts_with($basis, 'https://')) {
                URL::forceScheme('https');
            }
            config(['app.url' => $basis]);
        }

        // Sommerferienmodus auch ohne Zeitplan-Container: nach einer Webanfrage
        // (wenn die Antwort schon raus ist) höchstens stündlich prüfen
        // Summer holiday mode even without a scheduler container: after a web request
        // (once the response has been sent) check at most once an hour
        if (! $this->app->runningInConsole()) {
            $this->app->terminating(fn () => \App\Support\SummerHoliday::checkInBackground());
        }
    }
}

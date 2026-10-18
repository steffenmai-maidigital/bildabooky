<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Sicherheits-Header für jede Antwort.
 *
 * - Content-Security-Policy: Skripte, Bilder, Töne und Verbindungen nur vom
 *   eigenen Server. Selbst wenn es jemand schaffte, Code einzuschleusen,
 *   könnte er nichts von fremden Servern nachladen oder Daten dorthin
 *   schicken. ('unsafe-inline' ist nötig, weil die Seiten kleine Skripte
 *   direkt enthalten; Inhalte aus Büchern gelangen nie als HTML in die Seite.)
 * - kein Einbetten in fremde Seiten (Clickjacking)
 * - Kamera und Mikrofon nur für BilDaBooky selbst
 * - hinter HTTPS: Cookies nur verschlüsselt senden, HSTS
 *
 * Security headers for every response.
 *
 * - Content-Security-Policy: scripts, images, sounds and connections only from
 *   our own server. Even if someone managed to inject code,
 *   it could not load anything from other servers or send data
 *   there. ('unsafe-inline' is needed because the pages contain small scripts
 *   directly; content from books never reaches the page as HTML.)
 * - no embedding in other sites (clickjacking)
 * - camera and microphone only for BilDaBooky itself
 * - behind HTTPS: send cookies only encrypted, HSTS
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $https = $request->isSecure();
        if ($https) {
            // Sitzungs- und CSRF-Cookie nie unverschlüsselt übertragen
            // Never transmit the session and CSRF cookie unencrypted
            config(['session.secure' => true]);
        }

        $response = $next($request);
        $h = $response->headers;

        // Logo-Auslieferung setzt eine eigene, strengere
        // logo delivery sets its own, stricter one
        if (! $h->has('Content-Security-Policy')) {
            $h->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline'",
                "style-src 'self' 'unsafe-inline'",
                "img-src 'self' data: blob:",
                "media-src 'self' data: blob:",
                "connect-src 'self'",
                "font-src 'self'",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'self'",
            ]));
        }
        $h->set('X-Frame-Options', 'SAMEORIGIN');
        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('Referrer-Policy', 'same-origin');
        $h->set('Permissions-Policy', 'camera=(self), microphone=(self), geolocation=(), payment=(), usb=()');
        $h->set('Cross-Origin-Opener-Policy', 'same-origin');
        if ($https) {
            $h->set('Strict-Transport-Security', 'max-age=15552000');
        }
        return $response;
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Bilder und Töne nicht als „vorherige Seite“ merken. Laravel speichert
 * jeden GET-Aufruf, der kein Ajax ist, für back() — ohne diese Bremse
 * führten Formularfehler und der Sprachwechsel auf eine Bilddatei.
 * StartSession prüft $request->ajax() erst nach der Antwort.
 *
 * Do not remember images and sounds as the “previous page”. Laravel stores
 * every non-Ajax GET request for back() — without this throttle,
 * form errors and the language switch led to an image file.
 * StartSession checks $request->ajax() only after the response.
 */
class NotPreviousUrl
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        return $response;
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request, string $code)
    {
        abort_unless(array_key_exists($code, config('bildabooky.locales')), 404);

        $request->session()->put('locale', $code);
        $request->user()?->forceFill(['locale' => $code])->save();

        // zurück zur Seite, von der aus gewechselt wurde (nur eigene Adressen)
        // back to the page the switch was made from (own addresses only)
        $target = (string) $request->headers->get('referer');
        if (! str_starts_with($target, $request->getSchemeAndHttpHost() . '/')) {
            $target = route('home');
        }

        return redirect()->to($target)
            ->withCookie(cookie()->forever('locale', $code, httpOnly: true));
    }
}

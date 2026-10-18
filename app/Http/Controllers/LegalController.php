<?php

namespace App\Http\Controllers;

use App\Support\LegalPages;

/**
 * Impressum und Datenschutz — öffentlich, auch ohne Anmeldung
 *
 * Imprint (legal notice) and privacy policy — public, even without sign-in
 */
class LegalController extends Controller
{
    public function imprint()
    {
        return $this->show('imprint');
    }

    public function privacy()
    {
        return $this->show('privacy');
    }

    private function show(string $type)
    {
        abort_unless(LegalPages::enabled($type), 404);
        if ($link = LegalPages::link($type)) {
            return redirect()->away($link);
        }
        return view('legal.page', [
            'title' => LegalPages::title($type),
            'html'  => LegalPages::html(LegalPages::text($type)),
        ]);
    }
}

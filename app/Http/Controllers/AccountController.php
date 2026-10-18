<?php

namespace App\Http\Controllers;

use App\Support\Totp;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Eigenes Konto: Passwort ändern, Zwei-Faktor-Anmeldung ein-/ausschalten
 *
 * Own account: change password, switch two-factor sign-in on/off
 */
class AccountController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $setup = null;

        // Einrichtung läuft: Geheimnis liegt (noch unbestätigt) in der Sitzung
        // Setup in progress: the secret is held (not yet confirmed) in the session
        if ($secret = $request->session()->get('2fa.secret')) {
            $uri = Totp::uri($secret, $user->username, \App\Support\Branding::school() . ' BilDaBooky');
            $svg = (new Writer(new ImageRenderer(new RendererStyle(220, 1), new SvgImageBackEnd())))->writeString($uri);
            $setup = ['secret' => trim(chunk_split($secret, 4, ' ')), 'qr' => $svg];
        }

        return view('account', [
            'user'  => $user,
            'setup' => $setup,
            'codes' => $request->session()->get('2fa.codes'),
        ]);
    }

    public function password(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isLocal(), 403, 'Schulkonten ändern ihr Passwort im Schulverzeichnis.');
        $min = $user->isPupil() ? 4 : 8;
        $data = $request->validate([
            'current'  => ['required', 'string'],
            'password' => ['required', 'string', "min:$min", 'max:200', 'confirmed'],
        ]);

        if (! Hash::check($data['current'], $user->password)) {
            return back()->withErrors(['current' => __('Das bisherige Passwort stimmt nicht.')]);
        }
        if (Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['password' => __('Bitte ein neues Passwort wählen.')]);
        }

        $user->password = $data['password'];
        $user->must_change_password = false;
        if ($user->hasCard()) {
            $user->setCardPassword($data['password']);   // Karte bleibt aktuell / card stays up to date
        }
        $user->save();

        return redirect()->route('account')->with('ok', __('Passwort geändert.'));
    }

    /**
     * Bestätigung sicherheitsrelevanter Schritte: eigene Konten mit Passwort,
     * Schulkonten mit einem aktuellen Code der Authenticator-App.
     *
     * Confirmation of security-relevant steps: own accounts with their password,
     * school accounts with a current code from the authenticator app.
     */
    private function confirmed(Request $request): bool
    {
        $request->validate(['password' => ['required', 'string', 'max:200']]);
        $user = $request->user();
        $input = (string) $request->input('password');
        if ($user->isLocal()) {
            return Hash::check($input, $user->password);
        }

        return $user->hasTwoFactor() && Totp::verify($user->two_factor_secret, preg_replace('/\s+/', '', $input)) !== null;
    }

    /** Fuchs-Helfer für sich selbst ein- oder ausschalten / Switch the fox helper on or off for oneself */
    public function fox(Request $request)
    {
        $on = $request->boolean('fox');
        $request->user()->forceFill(['fox' => $on])->save();
        return redirect()->route('account')->with('ok', $on ? __('Der Fuchs ist wieder da!') : __('Der Fuchs macht Pause.'));
    }

    public function startTwoFactor(Request $request)
    {
        abort_unless($request->user()->twoFactorAllowed(), 403);   // Kinder bzw. Gruppe „aus“ / children or group “off”
        $request->session()->put('2fa.secret', Totp::newSecret());
        return redirect()->route('account');
    }

    public function confirmTwoFactor(Request $request)
    {
        abort_unless($request->user()->twoFactorAllowed(), 403);
        $request->validate(['code' => ['required', 'string', 'max:10']]);
        $secret = $request->session()->get('2fa.secret');
        $step = $secret ? Totp::verify($secret, preg_replace('/\s+/', '', $request->input('code'))) : null;
        if ($step === null) {
            return back()->withErrors(['code' => __('Der Code passt nicht. Uhrzeit am Handy prüfen und den aktuellen Code eingeben.')]);
        }

        $user = $request->user();
        $user->forceFill([
            'two_factor_secret'       => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_last_step'    => $step,
        ])->save();
        $request->session()->forget('2fa.secret');

        return redirect()->route('account')
            ->with('2fa.codes', $user->newRecoveryCodes())
            ->with('ok', __('Zwei-Faktor-Anmeldung ist eingeschaltet.'));
    }

    public function recoveryCodes(Request $request)
    {
        abort_unless($request->user()->twoFactorAllowed(), 403);
        if (! $this->confirmed($request)) {
            return back()->withErrors(['password' => __('Das Passwort stimmt nicht.')]);
        }
        return redirect()->route('account')->with('2fa.codes', $request->user()->newRecoveryCodes());
    }

    public function disableTwoFactor(Request $request)
    {
        abort_unless($request->user()->twoFactorAllowed(), 403);
        $user = $request->user();
        if ($user->twoFactorRequired()) {
            return back()->withErrors(['password' => __('Für deine Gruppe ist die Zwei-Faktor-Anmeldung vorgeschrieben.')]);
        }
        if (! $this->confirmed($request)) {
            return back()->withErrors(['password' => __('Das Passwort stimmt nicht.')]);
        }
        $user->disableTwoFactor();
        return redirect()->route('account')->with('ok', __('Zwei-Faktor-Anmeldung ist ausgeschaltet.'));
    }
}

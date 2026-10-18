<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\ClassSessionController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Alte (deutsche) Adressen leiten dauerhaft auf die neuen um (Standard 10);
// zuerst geladen, damit alte Handbuch-Kennungen vor der Anmeldung umleiten
// Old (German) addresses redirect permanently to the new ones (standard 10);
// loaded first so that old manual slugs redirect before the sign-in check
require __DIR__ . '/legacy.php';

// Schullogo (öffentlich, erscheint schon auf der Anmeldeseite)
// School logo (public, already appears on the sign-in page)
Route::get('/brand/logo', [SettingsController::class, 'logo'])->name('brand.logo');

// Impressum und Datenschutz (öffentlich, vom Admin ein- und ausschaltbar)
// Imprint and privacy policy (public, can be switched on and off by the admin)
Route::get('/imprint', [LegalController::class, 'imprint'])->name('imprint');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('privacy');

// Sprache wählen (auch vor der Anmeldung)
// Choose language (also before sign-in)
Route::get('/locale/{code}', LocaleController::class)->name('locale.switch');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:30,1');
    Route::get('/login/code', [AuthController::class, 'showTwoFactor'])->name('login.code');
    Route::post('/login/code', [AuthController::class, 'twoFactor'])->middleware('throttle:15,1');
    Route::get('/login/session-code', [AuthController::class, 'showSessionCode'])->name('login.session-code');
    Route::post('/login/session-code', [AuthController::class, 'sessionCode'])->middleware('throttle:20,1');
});

// direkt aufgerufene Abmelde-Adresse: nicht mit „Method Not Allowed“ enden
// logout address opened directly: do not end with “Method Not Allowed”
Route::get('/logout', fn () => redirect()->route('home'));

Route::middleware(['auth', 'class-session'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Eigenes Konto: Passwort und Zwei-Faktor
    // Own account: password and two-factor
    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::put('/account/password', [AccountController::class, 'password'])->name('account.password');
    Route::put('/account/fox', [AccountController::class, 'fox'])->name('account.fox');
    Route::post('/account/2fa', [AccountController::class, 'startTwoFactor'])->name('account.2fa.start');
    Route::post('/account/2fa/confirm', [AccountController::class, 'confirmTwoFactor'])->name('account.2fa.confirm');
    Route::post('/account/2fa/codes', [AccountController::class, 'recoveryCodes'])->name('account.2fa.codes');
    Route::delete('/account/2fa', [AccountController::class, 'disableTwoFactor'])->name('account.2fa.disable');

    // Hilfe (Standard 03): Kurzanleitung, System, Lizenz, Handbücher, Changelog, Fehler, Support
    // Help (Standard 03): quick guide, system, licence, manuals, changelog, errors, support
    Route::get('/help', [HelpController::class, 'show'])->name('help');
    Route::get('/help/manual/{doc}', [HelpController::class, 'doc'])->name('help.doc');
});

Route::middleware(['auth', 'class-session', 'password-changed'])->group(function () {
    Route::get('/', [LibraryController::class, 'index'])->name('home');

    // Bücher
    // Books
    Route::post('/books', [BookController::class, 'store'])->name('books.store');
    Route::get('/previews', [BookController::class, 'previews'])->name('books.previews')->middleware('no-history');
    Route::get('/books/{book}', [BookController::class, 'read'])->name('books.read');
    Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
    Route::get('/books/{book}/print', [BookController::class, 'print'])->name('books.print');
    Route::get('/books/{book}/download', [BookController::class, 'download'])->name('books.download');
    Route::patch('/books/{book}', [BookController::class, 'update'])->name('books.update');
    Route::post('/books/{book}/copy', [BookController::class, 'duplicate'])->name('books.copy');
    Route::post('/books/{book}/start', [BookController::class, 'start'])->name('books.start');
    Route::post('/books/{book}/submit', [BookController::class, 'submit'])->name('books.submit');
    Route::post('/books/{book}/return', [BookController::class, 'giveBack'])->name('books.return');
    Route::get('/books/{book}/log', [BookController::class, 'log'])->name('books.log');
    Route::get('/books/{book}/versions', [BookController::class, 'versions'])->name('books.versions');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');

    // Seiten (vom Editor per fetch)
    // Pages (from the editor via fetch)
    Route::post('/books/{book}/pages', [PageController::class, 'store'])->name('pages.store');
    Route::put('/books/{book}/pages/{page}', [PageController::class, 'update'])->name('pages.update');
    Route::delete('/books/{book}/pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');
    Route::post('/books/{book}/order', [PageController::class, 'reorder'])->name('pages.order');
    Route::get('/books/{book}/state', [PageController::class, 'state'])->name('pages.state')->middleware('no-history');

    // Medien
    // höchstens 60 Uploads je Minute und Person (gegen Zumüllen des Speichers)
    // Media
    // at most 60 uploads per minute and person (against filling up the storage)
    Route::post('/books/{book}/media', [MediaController::class, 'store'])->name('media.store')->middleware('throttle:60,1');
    Route::get('/media/{media}', [MediaController::class, 'show'])->name('media.show')->middleware('no-history');

    // Lehrkräfte und Admins: Benutzer und Klassen
    // Teachers and admins: users and classes
    Route::middleware('teacher')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/bulk', [UserController::class, 'bulk'])->name('users.bulk');
        Route::get('/users/cards', [UserController::class, 'cards'])->name('users.cards');
        Route::get('/users/{user}', [UserController::class, 'edit'])->name('users.edit');
        Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password');
        Route::delete('/users/{user}/2fa', [UserController::class, 'resetTwoFactor'])->name('users.2fa');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/classes', [ClassController::class, 'index'])->name('classes.index');
        // Lehrkräfte tragen sich selbst in Klassen ein (Fächer wählen) bzw. aus
        // Teachers add themselves to classes (choose subjects) or remove themselves
        Route::get('/classes/{schoolClass}/join', [ClassController::class, 'join'])->name('classes.join');
        Route::put('/classes/{schoolClass}/join', [ClassController::class, 'saveJoin'])->name('classes.join.save');
        Route::delete('/classes/{schoolClass}/join', [ClassController::class, 'leave'])->name('classes.leave');
        // Anmelde-Sitzungen der Klasse (Lehrkräfte der Klasse und Admins)
        // Sign-in sessions of the class (teachers of the class and admins)
        Route::get('/classes/{schoolClass}/sessions', [ClassSessionController::class, 'index'])->name('classes.sessions');
        Route::post('/classes/{schoolClass}/sessions', [ClassSessionController::class, 'store'])->name('classes.sessions.start');
        Route::post('/classes/{schoolClass}/sessions/{session}/extend', [ClassSessionController::class, 'extend'])->name('classes.sessions.extend');
        Route::post('/classes/{schoolClass}/sessions/{session}/close', [ClassSessionController::class, 'close'])->name('classes.sessions.close');
    });

    Route::middleware('admin')->group(function () {
        Route::post('/classes', [ClassController::class, 'store'])->name('classes.store');
        Route::patch('/classes/{schoolClass}', [ClassController::class, 'update'])->name('classes.update');
        Route::delete('/classes/{schoolClass}', [ClassController::class, 'destroy'])->name('classes.destroy');
        Route::get('/classes/{schoolClass}/edit', [ClassController::class, 'edit'])->name('classes.edit');
        Route::get('/classes/{schoolClass}/subjects', fn (\App\Models\SchoolClass $schoolClass) => redirect()->route('classes.edit', $schoolClass))->name('classes.subjects');
        Route::put('/classes/{schoolClass}/subjects', [ClassController::class, 'saveSubjects'])->name('classes.subjects.save');
        Route::post('/subjects', [SubjectController::class, 'store'])->name('subjects.store');
        Route::patch('/subjects/{subject}', [SubjectController::class, 'update'])->name('subjects.update');
        Route::post('/subjects/{subject}/move', [SubjectController::class, 'move'])->name('subjects.move');
        Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');
        Route::get('/backup', [BackupController::class, 'download'])->name('backup');

        Route::get('/settings', [SettingsController::class, 'show'])->name('settings');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-directory', [SettingsController::class, 'testLdap'])->name('settings.test-directory');
        Route::post('/settings/logo', [SettingsController::class, 'uploadLogo'])->name('settings.logo');
        Route::delete('/settings/logo', [SettingsController::class, 'deleteLogo'])->name('settings.logo.delete');
        Route::delete('/help/log', [HelpController::class, 'clearLog'])->name('help.clear-log');
    });
});

// Unbekannte Adressen über die web-Middleware (Sitzung, Sprache) leiten, damit
// die 404-Seite in der gewählten Sprache erscheint
// Route unknown addresses through the web middleware (session, locale) so
// that the 404 page appears in the chosen language
Route::fallback(fn (\Illuminate\Http\Request $request) => \App\Support\ErrorPage::unknownAddress($request));

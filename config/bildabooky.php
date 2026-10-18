<?php

return [
    'school'      => env('SCHOOL_NAME', 'Unsere Grundschule'),
    // Link zum Quellcode in der Fußzeile (AGPL §13: Nutzer müssen an den
    // Quellcode der laufenden Fassung kommen — bei Änderungen eigenen Link setzen)
    // Link to the source code in the footer (AGPL §13: users must be able to get the
    // source code of the running version — set your own link if you make changes)
    'source_url'  => env('SOURCE_URL', ''),
    // Oberflächensprachen; fehlende Texte fallen auf Deutsch zurück.
    // Neue Sprache: lang/xx.json anlegen und hier eintragen.
    // Interface languages; missing texts fall back to German.
    // New language: create lang/xx.json and add it here.
    'locales'     => ['de' => 'Deutsch', 'en' => 'English', 'fr' => 'Français', 'es' => 'Español'],
    'demo'        => filter_var(env('DEMO_MODE', false), FILTER_VALIDATE_BOOLEAN),
    // Testsystem-Kennzeichnung: Banner „TESTSYSTEM“ und Hinweis unter Hilfe → System
    // Test system label: “TESTSYSTEM” banner and notice under Help → System
    'test_system' => filter_var(env('TEST_MODE', false), FILTER_VALIDATE_BOOLEAN),
    'admin'       => [
        'username' => env('ADMIN_USERNAME', 'admin'),
        // leer = zufälliges Startpasswort, steht beim ersten Start im Protokoll
        // empty = random initial password, shown in the log at first start
        'password' => env('ADMIN_PASSWORD', ''),
    ],
    // Rollen, für die Zwei-Faktor-Anmeldung Pflicht ist, z. B. "admin,teacher"
    // (alter Name „leiter“ gilt weiter)
    // Roles for which two-factor sign-in is mandatory, e.g. "admin,teacher"
    // (the old name "leiter" keeps working)
    'two_factor_required' => array_values(array_filter(array_map(
        fn ($role) => ['leiter' => 'teacher'][$role] ?? $role,
        array_map('trim', explode(',', (string) env('REQUIRE_2FA_ROLES', '')))))),
    'media_path'  => env('MEDIA_PATH', '/data/medien'),
    'backup'      => [
        'path' => env('BACKUP_PATH', '/backups'),
        'days' => (int) env('BACKUP_KEEP_DAYS', 14),
    ],
    'upload_kb'   => 20480,

    // Produkt und Ausgabe — die Community Edition ist die Open-Source-Fassung
    // Product and edition — the Community Edition is the open-source version
    'product'     => 'BilDaBooky',
    // Projektseite für Fragen und Fehlermeldungen (Community)
    // Project page for questions and bug reports (community)
    'community'   => env('COMMUNITY_URL', env('SOURCE_URL', '')),
    // Hinweise unter Hilfe → Support: Quellcode/Fehlermeldungen und Projektseite
    // Notes under Help → Support: source code/bug reports and project page
    'github'      => env('GITHUB_URL', 'https://github.com/steffenmai-maidigital/bildabooky'),
    'project_url' => env('PROJECT_URL', 'https://www.mai-digital.de'),

];

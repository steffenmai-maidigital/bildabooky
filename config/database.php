<?php

return [
    'connections' => [
        // SQLite für mehrere gleichzeitige Nutzer (Klassenbuch): WAL erlaubt
        // Lesen während des Schreibens, busy_timeout wartet statt
        // „database is locked“, IMMEDIATE macht Prüfen+Speichern atomar.
        // SQLite for several simultaneous users (class book): WAL allows
        // reading while writing, busy_timeout waits instead of
        // “database is locked”, IMMEDIATE makes check+save atomic.
        'sqlite' => [
            'driver'                  => 'sqlite',
            'url'                     => env('DB_URL'),
            'database'                => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix'                  => '',
            'prefix_indexes'          => null,
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout'            => 10000,
            'journal_mode'            => 'wal',
            'synchronous'             => 'normal',
            'transaction_mode'        => 'IMMEDIATE',
            'pragmas'                 => [],
        ],
    ],
];

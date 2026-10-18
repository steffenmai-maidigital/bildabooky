<?php

return [
    'disks' => [
        // Laravel liefert die Speicherfläche „local“ sonst über /storage/{path}
        // aus (signierte Links). BilDaBooky braucht das nicht — abgeschaltet.
        // Otherwise Laravel serves the “local” disk via /storage/{path}
        // (signed links). BilDaBooky does not need this — switched off.
        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app/private'),
            'serve'  => false,
            'throw'  => false,
        ],
        // Fotos, Zeichnungen und Tonaufnahmen — liegen NICHT öffentlich,
        // sondern werden nur nach Rechteprüfung ausgeliefert (MediaController)
        // Photos, drawings and sound recordings — NOT stored publicly,
        // but delivered only after a permission check (MediaController)
        'media' => [
            'driver' => 'local',
            'root'   => env('MEDIA_PATH', '/data/medien'),
            'throw'  => false,
        ],
    ],
];

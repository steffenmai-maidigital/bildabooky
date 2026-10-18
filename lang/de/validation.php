<?php

return [
    'required'         => 'Bitte :attribute ausfüllen.',
    'string'           => ':attribute muss ein Text sein.',
    'integer'          => ':attribute muss eine Zahl sein.',
    'boolean'          => ':attribute ist ungültig.',
    'array'            => ':attribute ist ungültig.',
    'present'          => ':attribute fehlt.',
    'in'               => ':attribute ist ungültig.',
    'exists'           => ':attribute gibt es nicht.',
    'unique'           => ':attribute ist schon vergeben.',
    'confirmed'        => 'Die beiden Passwörter stimmen nicht überein.',
    'current_password' => 'Das Passwort stimmt nicht.',
    'file'             => ':attribute muss eine Datei sein.',
    'regex'            => ':attribute hat ein ungültiges Format.',
    'min'              => ['string' => ':attribute braucht mindestens :min Zeichen.', 'numeric' => ':attribute muss mindestens :min sein.', 'array' => 'Mindestens :min Einträge.', 'file' => 'Mindestens :min KB.'],
    'max'              => ['string' => ':attribute darf höchstens :max Zeichen haben.', 'numeric' => ':attribute darf höchstens :max sein.', 'array' => 'Höchstens :max Einträge.', 'file' => 'Die Datei ist zu groß (höchstens :max KB).'],
    'attributes'       => [
        'username' => 'Benutzername', 'password' => 'Passwort', 'current' => 'bisheriges Passwort',
        'first_name' => 'Vorname', 'last_name' => 'Nachname', 'name' => 'Name', 'role' => 'Rolle',
        'school_class_id' => 'Klasse', 'title' => 'Titel', 'code' => 'Code', 'names' => 'Namensliste',
        'file' => 'Datei',
    ],
];

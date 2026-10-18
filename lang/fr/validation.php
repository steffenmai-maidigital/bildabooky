<?php

return [
    'required'         => 'Le champ :attribute est obligatoire.',
    'string'           => 'Le champ :attribute doit être un texte.',
    'integer'          => 'Le champ :attribute doit être un nombre.',
    'boolean'          => 'Le champ :attribute n\'est pas valide.',
    'array'            => 'Le champ :attribute n\'est pas valide.',
    'present'          => 'Le champ :attribute est manquant.',
    'in'               => 'Le champ :attribute n\'est pas valide.',
    'exists'           => ':attribute n\'existe pas.',
    'unique'           => ':attribute est déjà utilisé.',
    'confirmed'        => 'Les deux mots de passe ne correspondent pas.',
    'current_password' => 'Le mot de passe est incorrect.',
    'file'             => ':attribute doit être un fichier.',
    'regex'            => 'Le format de :attribute n\'est pas valide.',
    'min'              => ['string' => ':attribute doit contenir au moins :min caractères.', 'numeric' => ':attribute doit être au moins :min.', 'array' => 'Au moins :min éléments.', 'file' => 'Au moins :min Ko.'],
    'max'              => ['string' => ':attribute ne peut pas dépasser :max caractères.', 'numeric' => ':attribute ne peut pas dépasser :max.', 'array' => 'Au plus :max éléments.', 'file' => 'Le fichier est trop volumineux (:max Ko au maximum).'],
    'attributes'       => [
        'username' => 'nom d\'utilisateur', 'password' => 'mot de passe', 'current' => 'mot de passe actuel',
        'first_name' => 'prénom', 'last_name' => 'nom', 'name' => 'nom', 'role' => 'rôle',
        'school_class_id' => 'classe', 'title' => 'titre', 'code' => 'code', 'names' => 'liste de noms',
        'file' => 'fichier',
    ],
];

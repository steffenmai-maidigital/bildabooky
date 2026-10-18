<?php

return [
    'required'         => 'El campo :attribute es obligatorio.',
    'string'           => 'El campo :attribute debe ser un texto.',
    'integer'          => 'El campo :attribute debe ser un número.',
    'boolean'          => 'El campo :attribute no es válido.',
    'array'            => 'El campo :attribute no es válido.',
    'present'          => 'Falta el campo :attribute.',
    'in'               => 'El campo :attribute no es válido.',
    'exists'           => ':attribute no existe.',
    'unique'           => ':attribute ya está en uso.',
    'confirmed'        => 'Las dos contraseñas no coinciden.',
    'current_password' => 'La contraseña no es correcta.',
    'file'             => ':attribute debe ser un archivo.',
    'regex'            => 'El formato de :attribute no es válido.',
    'min'              => ['string' => ':attribute debe tener al menos :min caracteres.', 'numeric' => ':attribute debe ser al menos :min.', 'array' => 'Al menos :min elementos.', 'file' => 'Al menos :min KB.'],
    'max'              => ['string' => ':attribute no puede tener más de :max caracteres.', 'numeric' => ':attribute no puede ser mayor que :max.', 'array' => 'Como máximo :max elementos.', 'file' => 'El archivo es demasiado grande (máximo :max KB).'],
    'attributes'       => [
        'username' => 'nombre de usuario', 'password' => 'contraseña', 'current' => 'contraseña actual',
        'first_name' => 'nombre', 'last_name' => 'apellido', 'name' => 'nombre', 'role' => 'rol',
        'school_class_id' => 'clase', 'title' => 'título', 'code' => 'código', 'names' => 'lista de nombres',
        'file' => 'archivo',
    ],
];

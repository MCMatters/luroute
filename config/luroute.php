<?php

declare(strict_types=1);

return [
    // Namespace for the global access from the "window" object in JavaScript.
    'namespace' => 'luroute',

    // The directory where file will be stored.
    'path' => base_path('public/js'),

    // The name of the generated file.
    'filename' => 'luroute',

    'exclude' => [
        // Uri paths of excluded routes.
        // For example: "/admin", "/admin/*"
        'uri' => [],

        // Names of excluded routes.
        // For example: "admin", "admin.*"
        'name' => [],
    ],
];

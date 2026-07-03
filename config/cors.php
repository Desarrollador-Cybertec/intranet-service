<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | El frontend (Vite dev en :5173) consume la API con tokens Bearer, no
    | cookies. Permitimos los orígenes de desarrollo del front de Insumma
    | y el dominio de producción (intranet.insummabg.net → service.insummabg.net).
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://localhost:3000',
        'https://intranet.insummabg.net',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Bearer tokens en localStorage → no requiere credenciales de cookie.
    'supports_credentials' => false,

];

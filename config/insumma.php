<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dominios permitidos para el registro público
    |--------------------------------------------------------------------------
    | Va en config y no en una tabla editable desde la UI: es un control de
    | seguridad, y una interfaz comprometida no debe poder añadir "gmail.com".
    */
    'allowed_registration_domains' => array_filter(array_map(
        'trim',
        explode(',', env('INSUMMA_ALLOWED_DOMAINS', 'insumma.co,insummabg.net,cybertec.com.co'))
    )),

    /*
    |--------------------------------------------------------------------------
    | URL del SPA (frontend)
    |--------------------------------------------------------------------------
    | Usada para construir enlaces que el usuario abre en el navegador (p. ej.
    | el enlace de restablecimiento de contraseña), a diferencia de APP_URL que
    | es la URL de esta API.
    */
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),
];

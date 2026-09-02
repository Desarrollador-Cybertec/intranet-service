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

    /*
    |--------------------------------------------------------------------------
    | Destinatarios de los formularios dinámicos (Parte B)
    |--------------------------------------------------------------------------
    | Orden de resolución real (ver FormController::resolveRecipients): primero
    | modules.config.recipients del módulo que expone el formulario (editable
    | desde Configuraciones sin deploy), luego esta lista por slug, y por
    | último 'default'. Si las tres quedan vacías, la solicitud se guarda
    | igual y se registra un Log::warning en vez de fallar el envío.
    | TODO: pendiente el correo real de RRHH/SST del cliente.
    */
    'forms' => [
        'destinatarios' => [
            'certificado-laboral' => array_filter(array_map('trim', explode(',', env('INSUMMA_FORMS_TO_RRHH', '')))),
            'certificado-ingresos-retenciones' => array_filter(array_map('trim', explode(',', env('INSUMMA_FORMS_TO_RRHH', '')))),
            'condiciones-inseguras' => array_filter(array_map('trim', explode(',', env('INSUMMA_FORMS_TO_SST', '')))),
            'accidente-trabajo' => array_filter(array_map('trim', explode(',', env('INSUMMA_FORMS_TO_SST', '')))),
            'default' => array_filter(array_map('trim', explode(',', env('INSUMMA_FORMS_TO_DEFAULT', '')))),
        ],
    ],
];

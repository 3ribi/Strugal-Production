<?php

return [

    /*
    |--------------------------------------------------------------------------
    | First administrator
    |--------------------------------------------------------------------------
    |
    | Used by `php artisan app:install` (run by the Docker entrypoint) to create
    | the first administrator when none exists yet. If no password is given, a
    | random one is generated and printed once.
    |
    */

    'admin_name' => env('ADMIN_NAME', 'Administrateur'),

    'admin_email' => env('ADMIN_EMAIL', 'admin@example.com'),

    'admin_password' => env('ADMIN_PASSWORD'),

];

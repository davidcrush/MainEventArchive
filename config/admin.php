<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Initial Admin User (Seeder)
    |--------------------------------------------------------------------------
    |
    | Used by DatabaseSeeder to create the first staff account for Filament.
    | Set ADMIN_PASSWORD in .env on each environment — never commit real values.
    |
    */

    'name' => env('ADMIN_NAME', 'David Crush'),

    'email' => env('ADMIN_EMAIL', 'david@davidcrush.com'),

    'password' => env('ADMIN_PASSWORD'),

];

<?php

return [
    'guard' => 'web',
    'passwords' => 'users',

    // SIRGA ASN: login memakai NIP, tanpa registrasi publik, tanpa reset via email
    // (reset password dilakukan Super Admin).
    'username' => 'nip',
    'email' => 'email',
    'lowercase_usernames' => false,

    'home' => '/',
    'prefix' => '',
    'domain' => null,
    'middleware' => ['web'],

    'limiters' => [
        'login' => 'login',
    ],

    'views' => true,

    'features' => [],
];

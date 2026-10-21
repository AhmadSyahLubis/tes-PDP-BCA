<?php

return [
    'secret' => env('JWT_SECRET'),
    'issuer' => env('JWT_ISSUER', env('APP_URL', 'http://localhost')),
    'audience' => env('JWT_AUDIENCE', env('APP_URL', 'http://localhost')),
    'access_seconds' => 900,
    'refresh_seconds' => 604800,
    'access_cookie' => 'credit_access',
    'refresh_cookie' => 'credit_refresh',
];

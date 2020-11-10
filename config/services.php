<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'firefly' => [
        'server' => env('FIREFLY_SERVER'),
        'client_id' => env('FIREFLY_CLIENT_ID'),
        'client_secret' => env('FIREFLY_CLIENT_SECRET'),
        'user' => env('FIREFLY_USER'),
        'pass' => env('FIREFLY_PASSWORD'),
        'access_token' => env('ACCESS_TOKEN'),
        'refresh_token' => env('REFRESH_TOKEN')
    ],
    'telegram' => [
        'key' => env('TELEGRAM_TOKEN'),
        'url' => env('TELEGRAM_URL'),
        'allowed' => array('303437427', '1146462100')
    ]

];

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

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'mellat' => [
        'terminal_id' => env('MELLAT_TERMINAL_ID'),
        'user' => env('MELLAT_USER'),
        'password' => env('MELLAT_PASSWORD'),
        'wsdl' => env('MELLAT_WSDL'),
        'startpay' => env('MELLAT_STARTPAY'),
        'callback' => env('MELLAT_CALLBACK'),
        'allowed_ips' => array_filter(array_map('trim', explode(',', env('MELLAT_ALLOWED_IPS','')))),
        'amount_multiplier' => (int) env('MELLAT_AMOUNT_MULTIPLIER', 1),
    ],


];

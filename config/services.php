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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'corex' => [
        'base_url' => env('COREX_API_BASE', 'http://91.99.130.85:8084/api/v1/website'),
        'api_key' => env('COREX_API_KEY'),
        'webhook_secret' => env('COREX_WEBHOOK_SECRET'),
        'timeout' => (int) env('COREX_API_TIMEOUT', 10),
        'cache_ttl' => (int) env('COREX_CACHE_TTL', 300),
        // When true, the site is served from the local demo dataset instead of
        // the live CoreX API (handy while the feed is unavailable).
        'demo' => (bool) env('COREX_DEMO', false),
        // Hosts whose listing images may be served through the white-border
        // trimming proxy. Anything else is left as a direct URL (and rejected
        // by the proxy) so this can never become an open image proxy.
        'media_hosts' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('COREX_MEDIA_HOSTS', 'staging.corexos.co.za,corexos.co.za,www.corexos.co.za')),
        ))),
    ],

];

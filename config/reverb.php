<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Reverb Server
    |--------------------------------------------------------------------------
    |
    | This option controls the default server connection that will be used
    | when running the Reverb server. You may manage multiple server
    | configurations within this file.
    |
    */

    'default' => env('REVERB_SERVER', 'reverb'),

    /*
    |--------------------------------------------------------------------------
    | Reverb Servers
    |--------------------------------------------------------------------------
    |
    | Here you may define the server connections that are available for
    | your application. A typical configuration includes a Reverb server
    | running locally over WebSocket.
    |
    */

    'servers' => [

        'reverb' => [
            'host' => env('REVERB_SERVER_HOST', '0.0.0.0'),
            'port' => env('REVERB_SERVER_PORT', 8080),
            'hostname' => env('REVERB_HOST'),
            'options' => [
                'tls' => [],
            ],
            'max_request_size' => env('REVERB_MAX_REQUEST_SIZE', 10_000),
            'pulse_ingest_interval' => env('REVERB_PULSE_INGEST_INTERVAL', 15),
            'telescope_ingest_interval' => env('REVERB_TELESCOPE_INGEST_INTERVAL', 15),
            'scaling' => [
                'enabled' => env('REVERB_SCALING_ENABLED', false),
                'channel' => env('REVERB_SCALING_CHANNEL', 'reverb'),
                'server' => [
                    'url' => env('REDIS_URL'),
                    'host' => env('REDIS_HOST', '127.0.0.1'),
                    'port' => env('REDIS_PORT', 6379),
                    'username' => env('REDIS_USERNAME'),
                    'password' => env('REDIS_PASSWORD'),
                    'database' => env('REDIS_DB', '0'),
                ],
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Reverb Applications
    |--------------------------------------------------------------------------
    |
    | Here you may define the applications that are allowed to connect to
    | your Reverb server. You can configure multiple applications to
    | share a single Reverb server instance.
    |
    */

    'apps' => [

        [
            'key' => env('REVERB_APP_KEY'),
            'role' => 'admin',
            'id' => env('REVERB_APP_ID'),
            'secret' => env('REVERB_APP_SECRET'),
            'allowed_origins' => ['*'],
            'ping_interval' => env('REVERB_APP_PING_INTERVAL', 30),
            'max_connections' => env('REVERB_APP_MAX_CONNECTIONS', 10000),
            'max_message_size' => env('REVERB_APP_MAX_MESSAGE_SIZE', 10000),
        ],

    ],

];

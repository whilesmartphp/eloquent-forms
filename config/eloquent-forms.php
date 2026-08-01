<?php

use Whilesmart\Forms\ResponseFormatters\DefaultResponseFormatter;

return [
    /*
    | Register the package's HTTP routes and the prefix they mount under.
    */
    'register_routes' => true,
    'route_prefix' => env('FORMS_ROUTE_PREFIX', 'api'),

    /*
    | Response envelope used by the package controllers. Swap for your own
    | implementation of ResponseFormatterInterface to change the shape.
    */
    'response_formatter' => DefaultResponseFormatter::class,

    /*
    | Queue connection used to fan a submission out to its destinations.
    | Null uses the default connection.
    */
    'queue' => env('FORMS_QUEUE', null),

    /*
    | Spam protection applied to every public submission.
    */
    'protection' => [
        // Any field whose name is listed here must arrive empty. Bots fill them.
        'honeypot_fields' => ['_gotcha'],
        // Reject submissions that arrive faster than this after the form renders.
        // Requires the form to post a `_started_at` unix-ms timestamp.
        'min_submit_seconds' => env('FORMS_MIN_SUBMIT_SECONDS', 2),
        // Requests per minute per IP for the public submit endpoint.
        'rate_limit_per_minute' => env('FORMS_RATE_LIMIT', 10),
        // Max characters accepted for any single freeform value.
        'max_value_length' => 5000,
        // Human-verification challenge, resolved through `challenge_drivers`.
        // Null runs no challenge; the honeypot and time trap still apply.
        'challenge' => env('FORMS_CHALLENGE'),
    ],

    /*
    | Challenge driver map. Each key resolves to a class implementing
    | Whilesmart\Forms\Contracts\ChallengeVerifier. Add your own provider here
    | and every form gains it.
    */
    'challenge_drivers' => [
        'turnstile' => \Whilesmart\Forms\Challenges\TurnstileVerifier::class,
    ],

    'turnstile' => [
        // Only the secret belongs here. The site key is public and is baked
        // into the frontend bundle that renders the widget.
        'secret' => env('TURNSTILE_SECRET_KEY'),
        'token_field' => env('TURNSTILE_TOKEN_FIELD', 'cf_turnstile_response'),
        'timeout' => env('TURNSTILE_TIMEOUT', 5),
    ],

    /*
    | Destinations every submission is delivered to, in addition to being
    | stored. This is the agnostic fan-out: add a key here and a class in the
    | `drivers` map below, and every form gains that destination. A Form row
    | may override this list per form via its `destinations` column.
    */
    'destinations' => array_filter(array_map('trim', explode(',', env('FORMS_DESTINATIONS', 'mail')))),

    /*
    | Driver map. Each destination key resolves to a class implementing
    | Whilesmart\Forms\Contracts\FormDestination.
    */
    'drivers' => [
        'mail' => \Whilesmart\Forms\Destinations\MailDestination::class,
        'webhook' => \Whilesmart\Forms\Destinations\WebhookDestination::class,
        'mautic' => \Whilesmart\Forms\Destinations\MauticDestination::class,
        'smartpings' => \Whilesmart\Forms\Destinations\SmartpingsDestination::class,
    ],

    'mail' => [
        // Fallback recipient when a Form row has no `recipient_email`.
        'to' => env('FORMS_MAIL_TO', env('MAIL_FROM_ADDRESS')),
        'subject_prefix' => env('FORMS_MAIL_SUBJECT_PREFIX', 'New submission'),
    ],

    'webhook' => [
        'url' => env('FORMS_WEBHOOK_URL'),
        // Optional shared secret; sent as X-Forms-Signature (HMAC-SHA256 of the body).
        'secret' => env('FORMS_WEBHOOK_SECRET'),
    ],

    'mautic' => [
        'base_url' => env('MAUTIC_BASE_URL'),
        'client_id' => env('MAUTIC_CLIENT_ID'),
        'client_secret' => env('MAUTIC_CLIENT_SECRET'),
        'username' => env('MAUTIC_USERNAME'),
        'password' => env('MAUTIC_PASSWORD'),
    ],

    'smartpings' => [
        'client_id' => env('SMARTPINGS_CLIENT_ID'),
        'secret_id' => env('SMARTPINGS_SECRET_ID'),
        // Team destination alerted on each submission.
        'notify' => env('FORMS_SMARTPINGS_NOTIFY'),
    ],
];

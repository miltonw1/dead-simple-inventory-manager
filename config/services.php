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

    'mercado_pago' => [
        'access_token' => env('MERCADO_PAGO_ACCESS_TOKEN'),
        'base_url' => env('MERCADO_PAGO_BASE_URL', 'https://api.mercadopago.com'),
        'back_url' => env('MERCADO_PAGO_BACK_URL'),
        'webhook_url' => env('MERCADO_PAGO_WEBHOOK_URL'),
        'plans' => [
            'monthly' => [
                'label' => '30 days',
                'amount' => (float) env('MERCADO_PAGO_MONTHLY_AMOUNT', 0),
                'currency' => env('MERCADO_PAGO_CURRENCY', 'ARS'),
                'days' => 30,
            ],
            'quarterly' => [
                'label' => '90 days',
                'amount' => (float) env('MERCADO_PAGO_QUARTERLY_AMOUNT', 0),
                'currency' => env('MERCADO_PAGO_CURRENCY', 'ARS'),
                'days' => 90,
            ],
            'yearly' => [
                'label' => '365 days',
                'amount' => (float) env('MERCADO_PAGO_YEARLY_AMOUNT', 0),
                'currency' => env('MERCADO_PAGO_CURRENCY', 'ARS'),
                'days' => 365,
            ],
        ],
        'payer_email_override' => env('MERCADO_PAGO_PAYER_EMAIL_OVERRIDE'),
    ],

];

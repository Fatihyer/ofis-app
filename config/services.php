<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => 'us-east-1',
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],
    'hermes' => [
    'key' => env('HERMES_API_KEY'),
],


    'stripe' => [
        'model' => App\Models\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'timeout' => env('STRIPE_TIMEOUT', 20),
        'default_sirket_id' => env('STRIPE_DEFAULT_SIRKET_ID', 2),
        'accounts' => [
            'parisvia' => [
                'secret' => env('STRIPE_PARISVIA_SECRET', env('STRIPE_SECRET')),
                'webhook_secret' => env('STRIPE_PARISVIA_WEBHOOK_SECRET', env('STRIPE_WEBHOOK_SECRET')),
            ],
            'francevia' => [
                'secret' => env('STRIPE_FRANCEVIA_SECRET'),
                'webhook_secret' => env('STRIPE_FRANCEVIA_WEBHOOK_SECRET'),
            ],
        ],
    ],
    'google_maps' => [
    'api_key' => env('GOOGLE_MAPS_API_KEY'),
      'routes_url' => 'https://routes.googleapis.com/directions/v2:computeRoutes',
      'routes_api_key' => env('GOOGLE_ROUTES_API_KEY'),
    ],

    'google_ads' => [
        'developer_token' => env('GOOGLE_ADS_DEVELOPER_TOKEN'),
        'customer_id' => env('GOOGLE_ADS_CUSTOMER_ID'),
        'login_customer_id' => env('GOOGLE_ADS_LOGIN_CUSTOMER_ID'),
        'client_id' => env('GOOGLE_ADS_CLIENT_ID'),
        'client_secret' => env('GOOGLE_ADS_CLIENT_SECRET'),
        'refresh_token' => env('GOOGLE_ADS_REFRESH_TOKEN'),
        'api_version' => env('GOOGLE_ADS_API_VERSION', 'v24'),
        'timeout' => (int) env('GOOGLE_ADS_TIMEOUT', 30),
        'form_lead_conversion_action' => env('GOOGLE_ADS_FORM_LEAD_CONVERSION_ACTION'),
        'form_lead_conversion_name' => env('GOOGLE_ADS_FORM_LEAD_CONVERSION_NAME', 'Paris Via - Form Lead'),
        'form_lead_conversion_value' => (float) env('GOOGLE_ADS_FORM_LEAD_CONVERSION_VALUE', 1),
    ],
    'twilio' => [
            'sid' => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'from' => env('TWILIO_FROM'),
            'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),
        ],
    'whatsapp_gateway' => [
        'url' => env('WHATSAPP_GATEWAY_URL', 'http://127.0.0.1:3001'),
        'token' => env('WHATSAPP_GATEWAY_TOKEN'),
        'webhook_secret' => env('WHATSAPP_WEBHOOK_SECRET'),
        'timeout' => (int) env('WHATSAPP_GATEWAY_TIMEOUT', 20),
    ],

    'vonage' => [
    	'whatsapp' => [
        	'from_phone_number' => env('WHATSAPP_PHONE_NUMBER'),
    	],
	],
    'openai' => [
  'key' => env('OPENAI_API_KEY'),
  'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
  'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
],

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5'),
        'timeout' => (int) env('ANTHROPIC_TIMEOUT', 30),
    ],
  'report' => [
    'token' => env('REPORT_API_TOKEN'),
],

    'pennylane' => [
        'base_url' => env('PENNYLANE_API_URL', 'https://app.pennylane.com/api/external/v2'),
        'token' => env('PENNYLANE_API_TOKEN', env('PENNYLANE_API_KEY', env('PENNYLANE_TOKEN'))),
        'tokens' => [
            'francevia' => env('PENNYLANE_FRANCEVIA_API_TOKEN', env('PENNYLANE_FRANCEVIA_API_KEY', env('PENNYLANE_API_TOKEN', env('PENNYLANE_API_KEY', env('PENNYLANE_TOKEN'))))),
            'parisvia' => env('PENNYLANE_PARISVIA_API_TOKEN', env('PENNYLANE_PARISVIA_API_KEY')),
        ],
        'timeout' => env('PENNYLANE_TIMEOUT', 20),
    ],

    'parisvia_wordpress' => [
        'lead_token' => env('PARISVIA_WORDPRESS_LEAD_TOKEN'),
    ],

];

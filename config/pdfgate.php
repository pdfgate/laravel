<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API key
    |--------------------------------------------------------------------------
    |
    | Your PDFGate API key from https://dashboard.pdfgate.com. Keys that start
    | with "live_" use the production API and keys that start with "test_"
    | use the sandbox API. No base URL setting is needed.
    |
    */

    'api_key' => env('PDFGATE_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    |
    | "secrets" holds one signing secret per webhook you created in the
    | dashboard, keyed by a name of your choice. Most apps need only the
    | "default" entry.
    |
    | When "auto_route" is true the package registers POST /pdfgate/webhook,
    | verified with the "default" secret. Set it to false and declare your
    | own endpoints in routes/web.php instead:
    |
    |     Route::pdfgateWebhook('pdfgate/webhook');
    |     Route::pdfgateWebhook('pdfgate/billing-webhook', 'billing');
    |
    | Every verified delivery dispatches PdfGate\Laravel\Events\WebhookReceived
    | plus one typed event per webhook event type.
    |
    */

    'webhook' => [
        'auto_route' => true,
        'secrets' => [
            'default' => env('PDFGATE_WEBHOOK_SECRET'),
        ],
    ],

];

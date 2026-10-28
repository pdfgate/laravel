# PDFGate for Laravel

Laravel integration for the [PDFGate](https://pdfgate.com) API, built on the official [PHP SDK](https://github.com/pdfgate/pdfgate-sdk-php).

[![CI](https://github.com/pdfgate/laravel/actions/workflows/ci.yml/badge.svg)](https://github.com/pdfgate/laravel/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/pdfgate/laravel.svg)](https://packagist.org/packages/pdfgate/laravel)
[![License](https://img.shields.io/packagist/l/pdfgate/laravel.svg)](LICENSE)

PDFGate lets you generate, process and sign PDFs from your Laravel app:

- HTML or URL to PDF
- Fillable forms and adding form fields
- Create signing envelopes from source documents
- Embedded signing inside your own application
- Flatten, compress, watermark and protect PDFs
- Extract PDF form data
- Receive signed webhook events as Laravel events

📘 API Documentation: https://pdfgate.com/documentation<br>
🧭 PHP SDK Reference: https://pdfgate.github.io/pdfgate-sdk-php/<br>
🔑 Dashboard & API keys: https://dashboard.pdfgate.com

## Requirements

- PHP 8.2 or newer
- Laravel 12 or 13
- `ext-curl` and `ext-json`

## Installation

```bash
composer require pdfgate/laravel
```

The service provider and the `PdfGate` facade are registered automatically.

Add your API key to `.env`. Keys that start with `live_` call the production API and keys that start with `test_` call the sandbox API.

```dotenv
PDFGATE_API_KEY=test_your_api_key
PDFGATE_WEBHOOK_SECRET=your_webhook_secret
```

To change the defaults, publish the config file:

```bash
php artisan vendor:publish --tag=pdfgate-config
```

## Usage

Every method of the SDK's `PdfGateClient` is available on the `PdfGate` facade. See the [PHP SDK documentation](https://pdfgate.github.io/pdfgate-sdk-php/) for the full list of methods and request options.

### Generate a PDF

```php
use PdfGate\Laravel\Facades\PdfGate;

$document = PdfGate::generatePdf([
    'html' => view('invoices.show', ['invoice' => $invoice])->render(),
    'pageSizeType' => 'a4',
    'preSignedUrlExpiresIn' => 1200,
]);

$document->getId();
$document->getFileUrl();
```

### Send a PDF to the browser

```php
// Download
return PdfGate::download($document->getId(), 'invoice.pdf');

// Show inline in the browser's PDF viewer
return PdfGate::inline($document->getId(), 'invoice.pdf');
```

### Store a PDF on a disk

```php
PdfGate::store($document->getId(), 'invoices/' . $invoice->id . '.pdf', 's3');
```

The third argument is the disk name from `config/filesystems.php`. Leave it out to use the default disk.

### Create and send a signing envelope

```php
$envelope = PdfGate::createEnvelope([
    'requesterName' => 'Acme Inc.',
    'documents' => [
        [
            'sourceDocumentId' => $document->getId(),
            'name' => 'Service Agreement',
            'recipients' => [
                ['email' => 'anna@example.com', 'name' => 'Anna Smith'],
            ],
        ],
    ],
    'metadata' => ['orderId' => $order->id],
]);

PdfGate::sendEnvelope($envelope->getId());
```

### Dependency injection

The SDK client is bound as a singleton, so you can inject it instead of using the facade:

```php
use PdfGate\PdfGateClient;

public function __construct(private PdfGateClient $pdfGate)
{
}
```

Inject `PdfGate\Laravel\PdfGateManager` when you also want the `download()`, `inline()` and `store()` helpers.

## Webhooks

The package registers `POST /pdfgate/webhook`. Every request is checked against your webhook secret using the `x-pdfgate-signature` header. Requests with a missing, wrong or expired signature are rejected with a 403 before any event is dispatched.

1. Set `PDFGATE_WEBHOOK_SECRET` in `.env`.
2. In the [dashboard](https://dashboard.pdfgate.com), create a webhook pointing at `https://your-app.example/pdfgate/webhook`.
3. Listen for the events below.

### Events

A verified delivery dispatches `PdfGate\Laravel\Events\WebhookReceived` and, when the event type is known, one typed event:

| Webhook event | Laravel event |
| --- | --- |
| `envelope.sent` | `EnvelopeSent` |
| `envelope.completed` | `EnvelopeCompleted` |
| `envelope.expired` | `EnvelopeExpired` |
| `envelope.voided` | `EnvelopeVoided` |
| `envelope.deleted` | `EnvelopeDeleted` |
| `envelope.recipient.signed` | `EnvelopeRecipientSigned` |
| `envelope.recipient.activated` | `EnvelopeRecipientActivated` |
| `envelope.document.completed` | `EnvelopeDocumentCompleted` |

All event classes live in `PdfGate\Laravel\Events` and carry two properties. `$webhook` is the name of the endpoint that received the delivery, `default` unless you registered more (see below). `$payload` holds:

| Property | Description |
| --- | --- |
| `eventId` | Unique ID of the delivery. Use it to skip duplicates. |
| `type` | Event type, for example `envelope.completed`. |
| `timestamp` | When the event occurred, ISO 8601. |
| `resourceKind`, `resourceId` | The resource the event refers to. |
| `data` | Event data. Its shape depends on the event type, see the [webhook docs](https://pdfgate.com/documentation/webhooks). |
| `toArray()` | The full decoded request body. |

Laravel discovers listeners by their type-hinted event class:

```php
namespace App\Listeners;

use PdfGate\Laravel\Events\EnvelopeCompleted;
use PdfGate\Laravel\Facades\PdfGate;

class ArchiveSignedAgreement
{
    public function handle(EnvelopeCompleted $event): void
    {
        $envelope = $event->payload->data['envelope'];

        foreach ($envelope['documents'] as $document) {
            PdfGate::store($document['signedDocumentId'], 'agreements/' . $document['signedDocumentId'] . '.pdf');
        }
    }
}
```

Deliveries can be retried and arrive out of order. Fetch the current state with `PdfGate::getEnvelope()` before acting on it when ordering matters.

### Your own routes and several webhooks

Each webhook you create in the dashboard has its own signing secret. To use a different path, or to receive more than one webhook, publish the config, turn the automatic route off and declare the endpoints yourself with the `Route::pdfgateWebhook()` macro.

```php
// config/pdfgate.php
'webhook' => [
    'auto_route' => false,
    'secrets' => [
        'default' => env('PDFGATE_WEBHOOK_SECRET'),
        'billing' => env('PDFGATE_BILLING_WEBHOOK_SECRET'),
    ],
],
```

```php
// routes/web.php
Route::pdfgateWebhook('hooks/pdfgate');                   // verified with the "default" secret
Route::pdfgateWebhook('hooks/pdfgate-billing', 'billing'); // verified with the "billing" secret
```

The macro registers the controller with the signature middleware for that name, names the route `pdfgate.webhook.{name}`, and excludes Laravel's CSRF middleware so the route can live inside the `web` group. Events dispatched from the second endpoint have `$event->webhook === 'billing'`.

## Testing your application

Bind a client with a fake transport so your tests never reach the API:

```php
use PdfGate\Http\HttpTransportInterface;
use PdfGate\PdfGateClient;

$this->app->instance(
    PdfGateClient::class,
    PdfGateClient::createWithTransport('test_key', $fakeTransport)
);
```

`$fakeTransport` is any class implementing `HttpTransportInterface`. To test webhook listeners, use `Event::fake()` and dispatch the event class with a `WebhookPayload` built from `WebhookPayload::fromArray()`.

## Contributing

```bash
composer install
composer check
```

`composer check` validates `composer.json`, runs PHPStan and the test suite.

## License

MIT. See [LICENSE](LICENSE).

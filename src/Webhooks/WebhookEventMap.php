<?php

declare(strict_types=1);

namespace PdfGate\Laravel\Webhooks;

use PdfGate\Enum\WebhookEventType;
use PdfGate\Laravel\Events\EnvelopeCompleted;
use PdfGate\Laravel\Events\EnvelopeDeleted;
use PdfGate\Laravel\Events\EnvelopeDocumentCompleted;
use PdfGate\Laravel\Events\EnvelopeExpired;
use PdfGate\Laravel\Events\EnvelopeRecipientActivated;
use PdfGate\Laravel\Events\EnvelopeRecipientSigned;
use PdfGate\Laravel\Events\EnvelopeSent;
use PdfGate\Laravel\Events\EnvelopeVoided;
use PdfGate\Laravel\Events\WebhookEvent;

/**
 * Maps PDFGate webhook event types to the Laravel event class dispatched for them.
 */
final class WebhookEventMap
{
    /** @var array<string,class-string<WebhookEvent>> */
    private const MAP = [
        WebhookEventType::ENVELOPE_SENT => EnvelopeSent::class,
        WebhookEventType::ENVELOPE_COMPLETED => EnvelopeCompleted::class,
        WebhookEventType::ENVELOPE_EXPIRED => EnvelopeExpired::class,
        WebhookEventType::ENVELOPE_VOIDED => EnvelopeVoided::class,
        WebhookEventType::ENVELOPE_DELETED => EnvelopeDeleted::class,
        WebhookEventType::ENVELOPE_RECIPIENT_SIGNED => EnvelopeRecipientSigned::class,
        WebhookEventType::ENVELOPE_RECIPIENT_ACTIVATED => EnvelopeRecipientActivated::class,
        WebhookEventType::ENVELOPE_DOCUMENT_COMPLETED => EnvelopeDocumentCompleted::class,
    ];

    private function __construct()
    {
    }

    /**
     * @return class-string<WebhookEvent>|null Null for event types this package version does not know.
     */
    public static function eventClassFor(string $type): ?string
    {
        return self::MAP[$type] ?? null;
    }
}

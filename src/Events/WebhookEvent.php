<?php

declare(strict_types=1);

namespace PdfGate\Laravel\Events;

use PdfGate\Laravel\Webhooks\WebhookPayload;
use PdfGate\Laravel\Webhooks\WebhookRouteRegistrar;

/**
 * Base class for every event dispatched from a verified PDFGate webhook.
 */
abstract class WebhookEvent
{
    /**
     * @param WebhookPayload $payload The delivery as sent by PDFGate.
     * @param string $webhook Name of the webhook endpoint that received it, as configured in pdfgate.webhook.secrets.
     */
    public function __construct(
        public readonly WebhookPayload $payload,
        public readonly string $webhook = WebhookRouteRegistrar::DEFAULT_NAME
    ) {
    }
}

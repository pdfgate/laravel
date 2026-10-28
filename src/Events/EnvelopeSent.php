<?php

declare(strict_types=1);

namespace PdfGate\Laravel\Events;

/**
 * Dispatched for "envelope.sent": signing request emails were sent to recipients. Data holds the full envelope.
 */
final class EnvelopeSent extends WebhookEvent
{
}

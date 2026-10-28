<?php

declare(strict_types=1);

namespace PdfGate\Laravel\Events;

/**
 * Dispatched for "envelope.expired": the envelope expired before every document was signed. Data holds the full envelope.
 */
final class EnvelopeExpired extends WebhookEvent
{
}

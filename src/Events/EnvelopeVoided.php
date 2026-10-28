<?php

declare(strict_types=1);

namespace PdfGate\Laravel\Events;

/**
 * Dispatched for "envelope.voided": the sender voided the envelope. Data holds the full envelope with voidedAt and voidReason.
 */
final class EnvelopeVoided extends WebhookEvent
{
}

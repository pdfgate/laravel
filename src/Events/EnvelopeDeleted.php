<?php

declare(strict_types=1);

namespace PdfGate\Laravel\Events;

/**
 * Dispatched for "envelope.deleted": the sender deleted the envelope. Data holds envelopeId, previousStatus and deletedAt.
 */
final class EnvelopeDeleted extends WebhookEvent
{
}

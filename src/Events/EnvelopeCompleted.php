<?php

declare(strict_types=1);

namespace PdfGate\Laravel\Events;

/**
 * Dispatched for "envelope.completed": every document in the envelope was signed. Data holds the full envelope.
 */
final class EnvelopeCompleted extends WebhookEvent
{
}

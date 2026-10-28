<?php

declare(strict_types=1);

namespace PdfGate\Laravel\Events;

/**
 * Dispatched for "envelope.recipient.activated": it is now a recipient's turn to sign. Data holds sourceDocumentId, recipientId and activatedAt.
 */
final class EnvelopeRecipientActivated extends WebhookEvent
{
}

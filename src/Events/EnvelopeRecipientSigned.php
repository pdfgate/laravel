<?php

declare(strict_types=1);

namespace PdfGate\Laravel\Events;

/**
 * Dispatched for "envelope.recipient.signed": one recipient signed one document. Data holds sourceDocumentId, recipientId and signedAt.
 */
final class EnvelopeRecipientSigned extends WebhookEvent
{
}

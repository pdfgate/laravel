<?php

declare(strict_types=1);

namespace PdfGate\Laravel\Events;

/**
 * Dispatched for "envelope.document.completed": one document was signed by all its recipients. Data holds sourceDocumentId, signedDocumentId, auditLogDocumentId and documentStatus.
 */
final class EnvelopeDocumentCompleted extends WebhookEvent
{
}

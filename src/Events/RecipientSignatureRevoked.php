<?php

declare(strict_types=1);

namespace PdfGate\Laravel\Events;

/**
 * Dispatched for "recipient.signature.revoked": an active stored signature was removed from a recipient. Data holds recipientId and revokedAt.
 */
final class RecipientSignatureRevoked extends WebhookEvent
{
}

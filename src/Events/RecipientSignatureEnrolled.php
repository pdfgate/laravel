<?php

declare(strict_types=1);

namespace PdfGate\Laravel\Events;

/**
 * Dispatched for "recipient.signature.enrolled": a recipient stored a reusable signature for automatic signing. Data holds recipientId, enrolledAt and adoptionMethod.
 */
final class RecipientSignatureEnrolled extends WebhookEvent
{
}

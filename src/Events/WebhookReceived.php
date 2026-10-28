<?php

declare(strict_types=1);

namespace PdfGate\Laravel\Events;

/**
 * Dispatched for every verified webhook delivery, whatever its event type.
 */
final class WebhookReceived extends WebhookEvent
{
}

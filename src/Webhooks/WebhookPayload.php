<?php

declare(strict_types=1);

namespace PdfGate\Laravel\Webhooks;

use PdfGate\Exception\InvalidArgumentException;

/**
 * A webhook delivery as sent by PDFGate.
 */
final class WebhookPayload
{
    /**
     * @param string $eventId Unique ID of the delivery; use it to skip duplicates.
     * @param string $type Event type, for example "envelope.completed".
     * @param string $timestamp ISO 8601 time the event occurred.
     * @param string $resourceKind Kind of the resource the event refers to, for example "envelope".
     * @param string $resourceId ID of the resource the event refers to.
     * @param array<string,mixed> $data Event data; its shape depends on the event type.
     * @param array<string,mixed> $raw The decoded request body as received.
     */
    public function __construct(
        public readonly string $eventId,
        public readonly string $type,
        public readonly string $timestamp,
        public readonly string $resourceKind,
        public readonly string $resourceId,
        public readonly array $data,
        public readonly array $raw
    ) {
    }

    /**
     * @param array<string,mixed> $payload Decoded request body.
     * @throws InvalidArgumentException When a required field is missing or has the wrong type.
     */
    public static function fromArray(array $payload): self
    {
        $eventId = self::requireString($payload, 'eventId');
        $type = self::requireString($payload, 'event');
        $timestamp = self::requireString($payload, 'timestamp');

        $resource = $payload['resource'] ?? null;
        if (!is_array($resource)) {
            throw new InvalidArgumentException('Webhook payload field "resource" must be an object.');
        }

        $data = $payload['data'] ?? [];
        if (!is_array($data)) {
            throw new InvalidArgumentException('Webhook payload field "data" must be an object.');
        }

        return new self(
            $eventId,
            $type,
            $timestamp,
            self::requireString($resource, 'kind', 'resource.kind'),
            self::requireString($resource, 'id', 'resource.id'),
            $data,
            $payload
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->raw;
    }

    /**
     * @param array<string,mixed> $source
     */
    private static function requireString(array $source, string $key, ?string $label = null): string
    {
        $value = $source[$key] ?? null;

        if (!is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException(
                sprintf('Webhook payload field "%s" is required.', $label ?? $key)
            );
        }

        return $value;
    }
}

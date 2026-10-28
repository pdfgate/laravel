<?php

declare(strict_types=1);

namespace PdfGate\Laravel\Tests\Unit;

use Illuminate\Support\Facades\Event;
use PdfGate\Exception\InvalidConfigurationException;
use PdfGate\Laravel\Events\EnvelopeCompleted;
use PdfGate\Laravel\Events\EnvelopeRecipientSigned;
use PdfGate\Laravel\Events\WebhookReceived;
use PdfGate\Laravel\Tests\TestCase;

final class WebhookTest extends TestCase
{
    public function testValidDeliveryDispatchesGenericAndTypedEvents(): void
    {
        Event::fake();
        $body = (string) json_encode($this->webhookBody());


        $this->call('POST', '/pdfgate/webhook', [], [], [], $this->headers($this->signature($body)), $body)
            ->assertNoContent();

        Event::assertDispatched(WebhookReceived::class, static function (WebhookReceived $event): bool {
            return $event->payload->eventId === 'evt_1'
                && $event->payload->type === 'envelope.completed'
                && $event->payload->resourceKind === 'envelope'
                && $event->payload->resourceId === 'env_1'
                && $event->payload->data === ['envelope' => ['id' => 'env_1']];
        });
        Event::assertDispatched(EnvelopeCompleted::class, static function (EnvelopeCompleted $event): bool {
            return $event->payload->eventId === 'evt_1' && $event->webhook === 'default';
        });
        Event::assertNotDispatched(EnvelopeRecipientSigned::class);
    }

    public function testUnknownEventTypeDispatchesOnlyGenericEvent(): void
    {
        Event::fake();
        $body = (string) json_encode($this->webhookBody('envelope.future'));

        $this->call('POST', '/pdfgate/webhook', [], [], [], $this->headers($this->signature($body)), $body)
            ->assertNoContent();

        Event::assertDispatched(WebhookReceived::class);
        Event::assertNotDispatched(EnvelopeCompleted::class);
    }

    public function testMissingSignatureIsRejected(): void
    {
        Event::fake();
        $body = (string) json_encode($this->webhookBody());

        $this->call('POST', '/pdfgate/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body)
            ->assertForbidden();

        Event::assertNotDispatched(WebhookReceived::class);
    }

    public function testWrongSecretIsRejected(): void
    {
        Event::fake();
        $body = (string) json_encode($this->webhookBody());

        $this->call('POST', '/pdfgate/webhook', [], [], [], $this->headers($this->signature($body, 'other')), $body)
            ->assertForbidden();

        Event::assertNotDispatched(WebhookReceived::class);
    }

    public function testExpiredTimestampIsRejected(): void
    {
        $body = (string) json_encode($this->webhookBody());

        $this->call('POST', '/pdfgate/webhook', [], [], [], $this->headers($this->signature($body, null, time() - 600)), $body)
            ->assertForbidden();
    }

    public function testTamperedBodyIsRejected(): void
    {
        $body = (string) json_encode($this->webhookBody());
        $tampered = (string) json_encode($this->webhookBody('envelope.voided'));

        $this->call('POST', '/pdfgate/webhook', [], [], [], $this->headers($this->signature($body)), $tampered)
            ->assertForbidden();
    }

    public function testSignedButMalformedBodyIsBadRequest(): void
    {
        Event::fake();
        $body = (string) json_encode(['event' => 'envelope.completed']);

        $this->call('POST', '/pdfgate/webhook', [], [], [], $this->headers($this->signature($body)), $body)
            ->assertBadRequest();

        $this->call('POST', '/pdfgate/webhook', [], [], [], $this->headers($this->signature('not json')), 'not json')
            ->assertBadRequest();

        Event::assertNotDispatched(WebhookReceived::class);
    }

    public function testMissingSecretThrows(): void
    {
        $this->withoutExceptionHandling();
        $this->app['config']->set('pdfgate.webhook.secrets.default', null);
        $body = (string) json_encode($this->webhookBody());

        $this->expectException(InvalidConfigurationException::class);

        $this->call('POST', '/pdfgate/webhook', [], [], [], $this->headers($this->signature($body)), $body);
    }

    /**
     * @return array<string,string>
     */
    private function headers(string $signature): array
    {
        return [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PDFGATE_SIGNATURE' => $signature,
        ];
    }
}

<?php

declare(strict_types=1);

namespace PdfGate\Laravel\Tests\Unit;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use PdfGate\Exception\InvalidConfigurationException;
use PdfGate\Laravel\Events\EnvelopeCompleted;
use PdfGate\Laravel\Events\WebhookReceived;
use PdfGate\Laravel\Tests\TestCase;
use PdfGate\Laravel\Webhooks\WebhookRouteRegistrar;

final class WebhookMacroTest extends TestCase
{
    private const BILLING_SECRET = 'whsec_billing';

    /**
     * @param Application $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('pdfgate.webhook.auto_route', false);
        $app['config']->set('pdfgate.webhook.secrets.billing', self::BILLING_SECRET);
    }

    /**
     * The macro is only known at runtime, so the router is used untyped here.
     *
     * @param mixed $router
     */
    protected function defineRoutes($router): void
    {
        $router->pdfgateWebhook('hooks/pdfgate');
        $router->pdfgateWebhook('hooks/billing', 'billing');
        $router->middleware('web')->group(static function () use ($router): void {
            $router->pdfgateWebhook('web/billing', 'billing');
        });
    }

    public function testMacroRegistersNamedRoutes(): void
    {
        $this->assertSame('/hooks/pdfgate', parse_url(route('pdfgate.webhook.default'), PHP_URL_PATH));
        $this->assertSame('/hooks/billing', parse_url(route('pdfgate.webhook.billing'), PHP_URL_PATH));
        $this->assertFalse($this->app['router']->has('pdfgate.webhook'));
    }

    public function testEachEndpointVerifiesWithItsOwnSecret(): void
    {
        Event::fake();
        $body = (string) json_encode($this->webhookBody());

        $this->deliver('/hooks/billing', $body, $this->signature($body, self::BILLING_SECRET))->assertNoContent();
        $this->deliver('/hooks/billing', $body, $this->signature($body))->assertForbidden();
        $this->deliver('/hooks/pdfgate', $body, $this->signature($body))->assertNoContent();
        $this->deliver('/hooks/pdfgate', $body, $this->signature($body, self::BILLING_SECRET))->assertForbidden();

        Event::assertDispatchedTimes(WebhookReceived::class, 2);
    }

    public function testEventsCarryTheWebhookName(): void
    {
        Event::fake();
        $body = (string) json_encode($this->webhookBody());

        $this->deliver('/hooks/billing', $body, $this->signature($body, self::BILLING_SECRET))->assertNoContent();

        Event::assertDispatched(WebhookReceived::class, static fn (WebhookReceived $event): bool => $event->webhook === 'billing');
        Event::assertDispatched(EnvelopeCompleted::class, static fn (EnvelopeCompleted $event): bool => $event->webhook === 'billing');
    }

    public function testRouteInsideWebGroupExcludesCsrfMiddleware(): void
    {
        $excluded = $this->app['router']->getRoutes()->getByName('pdfgate.webhook.billing')->excludedMiddleware();

        $this->assertNotEmpty(WebhookRouteRegistrar::csrfMiddleware());
        foreach (WebhookRouteRegistrar::csrfMiddleware() as $middleware) {
            $this->assertContains($middleware, $excluded);
        }
        $this->assertContains('Illuminate\\Foundation\\Http\\Middleware\\ValidateCsrfToken', $excluded);

        Event::fake();
        $body = (string) json_encode($this->webhookBody());
        $this->deliver('/web/billing', $body, $this->signature($body, self::BILLING_SECRET))->assertNoContent();
    }

    public function testUnknownWebhookNameThrows(): void
    {
        $this->withoutExceptionHandling();
        $this->app['router']->pdfgateWebhook('hooks/other', 'other');
        $body = (string) json_encode($this->webhookBody());

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('"other"');

        $this->deliver('/hooks/other', $body, $this->signature($body));
    }

    /**
     * @return TestResponse<\Symfony\Component\HttpFoundation\Response>
     */
    private function deliver(string $uri, string $body, string $signature): TestResponse
    {
        return $this->call('POST', $uri, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PDFGATE_SIGNATURE' => $signature,
        ], $body);
    }
}

<?php

declare(strict_types=1);

namespace PdfGate\Laravel\Tests\Unit;

use PdfGate\Exception\InvalidConfigurationException;
use PdfGate\Laravel\Facades\PdfGate;
use PdfGate\Laravel\PdfGateManager;
use PdfGate\Laravel\Tests\TestCase;
use PdfGate\PdfGateClient;

final class ServiceProviderTest extends TestCase
{
    public function testClientAndManagerAreSingletons(): void
    {
        $this->assertSame($this->app->make(PdfGateClient::class), $this->app->make(PdfGateClient::class));
        $this->assertSame($this->app->make(PdfGateManager::class), $this->app->make('pdfgate'));
        $this->assertSame($this->app->make(PdfGateClient::class), PdfGate::client());
    }

    public function testClientIsBuiltFromConfiguredApiKey(): void
    {
        $this->app->forgetInstance(PdfGateClient::class);

        $this->assertInstanceOf(PdfGateClient::class, $this->app->make(PdfGateClient::class));
    }

    public function testMissingApiKeyThrowsOnResolve(): void
    {
        $this->app->forgetInstance(PdfGateClient::class);
        $this->app['config']->set('pdfgate.api_key', null);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('PDFGATE_API_KEY');

        $this->app->make(PdfGateClient::class);
    }

    public function testConfigIsMerged(): void
    {
        $this->assertTrue($this->app['config']->get('pdfgate.webhook.auto_route'));
    }

    public function testDefaultWebhookRouteIsRegistered(): void
    {
        $this->assertSame('/pdfgate/webhook', parse_url(route('pdfgate.webhook.default'), PHP_URL_PATH));
    }
}

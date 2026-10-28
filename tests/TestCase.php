<?php

declare(strict_types=1);

namespace PdfGate\Laravel\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use PdfGate\Laravel\PdfGateServiceProvider;
use PdfGate\Laravel\Tests\Support\FakeTransport;
use PdfGate\PdfGateClient;

abstract class TestCase extends Orchestra
{
    protected const API_KEY = 'test_laravel_key';
    protected const WEBHOOK_SECRET = 'whsec_laravel_test';

    protected FakeTransport $transport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transport = new FakeTransport();
        $this->app->instance(
            PdfGateClient::class,
            PdfGateClient::createWithTransport(self::API_KEY, $this->transport)
        );
    }

    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [PdfGateServiceProvider::class];
    }

    /**
     * @param Application $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('pdfgate.api_key', self::API_KEY);
        $app['config']->set('pdfgate.webhook.secrets.default', self::WEBHOOK_SECRET);
    }

    protected function signature(string $body, ?string $secret = null, ?int $timestamp = null): string
    {
        $timestamp ??= time();
        $hash = hash_hmac('sha256', $timestamp . '.' . $body, $secret ?? self::WEBHOOK_SECRET);

        return 't=' . $timestamp . ',v1=' . $hash;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    protected function webhookBody(string $type = 'envelope.completed', array $data = ['envelope' => ['id' => 'env_1']]): array
    {
        return [
            'eventId' => 'evt_1',
            'event' => $type,
            'timestamp' => '2026-10-27T12:00:00.000Z',
            'resource' => ['kind' => 'envelope', 'id' => 'env_1'],
            'data' => $data,
        ];
    }
}

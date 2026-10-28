<?php

declare(strict_types=1);

namespace PdfGate\Laravel\Tests\Unit;

use Illuminate\Foundation\Application;
use PdfGate\Laravel\Tests\TestCase;

final class WebhookRouteDisabledTest extends TestCase
{
    /**
     * @param Application $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('pdfgate.webhook.auto_route', false);
    }

    public function testNoRouteIsRegisteredWhenAutoRouteIsOff(): void
    {
        $this->assertFalse($this->app['router']->has('pdfgate.webhook.default'));
        $this->post('/pdfgate/webhook')->assertNotFound();
    }
}

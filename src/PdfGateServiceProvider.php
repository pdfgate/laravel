<?php

declare(strict_types=1);

namespace PdfGate\Laravel;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use PdfGate\Exception\InvalidConfigurationException;
use PdfGate\Laravel\Webhooks\WebhookRouteRegistrar;
use PdfGate\PdfGateClient;

/**
 * Registers the PDFGate client, facade, config, route macro and default webhook route.
 */
final class PdfGateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/pdfgate.php', 'pdfgate');

        $this->app->singleton(PdfGateClient::class, static function (Container $app): PdfGateClient {
            /** @var Config $config */
            $config = $app->make(Config::class);
            $apiKey = $config->get('pdfgate.api_key');

            if (!is_string($apiKey) || trim($apiKey) === '') {
                throw new InvalidConfigurationException(
                    'PDFGate API key is not set. Add PDFGATE_API_KEY to your .env file.'
                );
            }

            return new PdfGateClient($apiKey);
        });

        $this->app->singleton(PdfGateManager::class, static function (Container $app): PdfGateManager {
            /** @var PdfGateClient $client */
            $client = $app->make(PdfGateClient::class);
            /** @var ResponseFactory $responses */
            $responses = $app->make(ResponseFactory::class);
            /** @var FilesystemFactory $filesystems */
            $filesystems = $app->make(FilesystemFactory::class);

            return new PdfGateManager($client, $responses, $filesystems);
        });

        $this->app->alias(PdfGateManager::class, 'pdfgate');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [__DIR__ . '/../config/pdfgate.php' => $this->app->configPath('pdfgate.php')],
                'pdfgate-config'
            );
        }

        $this->registerRouteMacro();
        $this->registerDefaultWebhookRoute();
    }

    private function registerRouteMacro(): void
    {
        Router::macro('pdfgateWebhook', function (string $uri, string $name = WebhookRouteRegistrar::DEFAULT_NAME): Route {
            /** @var Router $this */
            return WebhookRouteRegistrar::register($this, $uri, $name);
        });
    }

    private function registerDefaultWebhookRoute(): void
    {
        /** @var Config $config */
        $config = $this->app->make(Config::class);

        if ($config->get('pdfgate.webhook.auto_route') !== true) {
            return;
        }

        /** @var Router $router */
        $router = $this->app->make(Router::class);

        WebhookRouteRegistrar::register($router, WebhookRouteRegistrar::DEFAULT_URI);
    }
}

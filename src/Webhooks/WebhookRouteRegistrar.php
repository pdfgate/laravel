<?php

declare(strict_types=1);

namespace PdfGate\Laravel\Webhooks;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use PdfGate\Laravel\Http\Controllers\WebhookController;
use PdfGate\Laravel\Http\Middleware\VerifyWebhookSignature;

/**
 * Registers a PDFGate webhook endpoint on a router.
 *
 * Exposed to applications as the Route::pdfgateWebhook() macro.
 */
final class WebhookRouteRegistrar
{
    public const DEFAULT_NAME = 'default';
    public const DEFAULT_URI = 'pdfgate/webhook';
    public const ROUTE_PARAMETER = 'pdfgateWebhook';

    /**
     * CSRF middleware shipped by supported Laravel versions. A route registered
     * inside the "web" group is excluded from whichever of these exist.
     *
     * @var list<string>
     */
    private const CSRF_MIDDLEWARE = [
        'Illuminate\\Foundation\\Http\\Middleware\\PreventRequestForgery',
        'Illuminate\\Foundation\\Http\\Middleware\\ValidateCsrfToken',
        'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
    ];

    private function __construct()
    {
    }

    /**
     * @param Router $router Router to register the route on.
     * @param string $uri URI of the endpoint, for example "pdfgate/webhook".
     * @param string $name Webhook name; its secret is read from pdfgate.webhook.secrets.{name}.
     */
    public static function register(Router $router, string $uri, string $name = self::DEFAULT_NAME): Route
    {
        return $router->post($uri, WebhookController::class)
            ->name('pdfgate.webhook.' . $name)
            ->defaults(self::ROUTE_PARAMETER, $name)
            ->middleware(VerifyWebhookSignature::class . ':' . $name)
            ->withoutMiddleware(self::csrfMiddleware());
    }

    /**
     * Class names of the CSRF middleware present in the installed Laravel version.
     *
     * @return list<string>
     */
    public static function csrfMiddleware(): array
    {
        $existing = [];

        foreach (self::CSRF_MIDDLEWARE as $class) {
            if (class_exists($class)) {
                $existing[] = $class;
            }
        }

        return $existing;
    }
}

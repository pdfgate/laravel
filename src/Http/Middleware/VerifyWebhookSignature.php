<?php

declare(strict_types=1);

namespace PdfGate\Laravel\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use PdfGate\Exception\InvalidConfigurationException;
use PdfGate\Exception\SignatureVerificationException;
use PdfGate\Laravel\Webhooks\WebhookRouteRegistrar;
use PdfGate\Webhook\WebhookSignatureVerifier;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Rejects requests whose x-pdfgate-signature header does not match the named webhook's secret.
 *
 * Use as "VerifyWebhookSignature::class . ':billing'" to verify with pdfgate.webhook.secrets.billing.
 */
final class VerifyWebhookSignature
{
    public function __construct(private readonly Config $config)
    {
    }

    /**
     * @param Closure(Request): Response $next
     * @param string $webhook Webhook name whose secret is used.
     */
    public function handle(Request $request, Closure $next, string $webhook = WebhookRouteRegistrar::DEFAULT_NAME): Response
    {
        $secret = $this->config->get('pdfgate.webhook.secrets.' . $webhook);

        if (!is_string($secret) || trim($secret) === '') {
            throw new InvalidConfigurationException($webhook === WebhookRouteRegistrar::DEFAULT_NAME
                ? 'PDFGate webhook secret is not set. Add PDFGATE_WEBHOOK_SECRET to your .env file.'
                : sprintf('No PDFGate webhook secret configured for "%s". Add it to pdfgate.webhook.secrets in config/pdfgate.php.', $webhook));
        }

        try {
            WebhookSignatureVerifier::verify(
                $secret,
                $request->headers->get('x-pdfgate-signature'),
                (string) $request->getContent()
            );
        } catch (SignatureVerificationException $exception) {
            throw new AccessDeniedHttpException($exception->getMessage(), $exception);
        }

        return $next($request);
    }
}

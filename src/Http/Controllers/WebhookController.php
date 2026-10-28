<?php

declare(strict_types=1);

namespace PdfGate\Laravel\Http\Controllers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use PdfGate\Exception\InvalidArgumentException;
use PdfGate\Laravel\Events\WebhookReceived;
use PdfGate\Laravel\Webhooks\WebhookEventMap;
use PdfGate\Laravel\Webhooks\WebhookPayload;
use PdfGate\Laravel\Webhooks\WebhookRouteRegistrar;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Turns a verified webhook delivery into Laravel events.
 */
final class WebhookController
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly ResponseFactory $responses
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $body = json_decode((string) $request->getContent(), true);

        if (!is_array($body)) {
            throw new BadRequestHttpException('Webhook body is not a JSON object.');
        }

        try {
            $payload = WebhookPayload::fromArray($body);
        } catch (InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }

        $webhook = $this->webhookName($request);

        $this->events->dispatch(new WebhookReceived($payload, $webhook));

        $eventClass = WebhookEventMap::eventClassFor($payload->type);
        if ($eventClass !== null) {
            $this->events->dispatch(new $eventClass($payload, $webhook));
        }

        return $this->responses->noContent();
    }

    private function webhookName(Request $request): string
    {
        $name = $request->route(WebhookRouteRegistrar::ROUTE_PARAMETER);

        return is_string($name) && $name !== '' ? $name : WebhookRouteRegistrar::DEFAULT_NAME;
    }
}

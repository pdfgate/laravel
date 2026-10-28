# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Service provider that binds `PdfGateClient` and `PdfGateManager` as singletons from `PDFGATE_API_KEY`.
- `PdfGate` facade exposing every SDK client method plus `download()`, `inline()` and `store()` helpers.
- Publishable `config/pdfgate.php`.
- Signed webhook endpoint at `POST /pdfgate/webhook`, registered automatically unless `pdfgate.webhook.auto_route` is false.
- `Route::pdfgateWebhook($uri, $name)` macro to declare webhook endpoints in the application's routes, each verified with its own secret from `pdfgate.webhook.secrets`.
- `WebhookReceived` event plus typed events for every envelope webhook event type, each carrying the payload and the receiving webhook's name.

<?php

declare(strict_types=1);

namespace PdfGate\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use PdfGate\Laravel\PdfGateManager;

/**
 * @phpstan-import-type GeneratePdfRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type UploadFileRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type FlattenPdfRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type CompressPdfRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type ProtectPdfRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type WatermarkPdfRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type ExtractPdfFormDataRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type GetDocumentQueryPayload from \PdfGate\Type\Types
 * @phpstan-import-type CreateEnvelopeRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type CreateEmbedLinkRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type CreateRecipientRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type UpdateRecipientRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type AddFormFieldsRequestPayload from \PdfGate\Type\Types
 * @phpstan-import-type CreateWebhookRequestPayload from \PdfGate\Type\Types
 *
 * @method static \PdfGate\PdfGateClient client()
 * @method static \Symfony\Component\HttpFoundation\StreamedResponse download(string $documentId, string $filename = 'document.pdf')
 * @method static \Symfony\Component\HttpFoundation\StreamedResponse inline(string $documentId, string $filename = 'document.pdf')
 * @method static bool store(string $documentId, string $path, ?string $disk = null)
 * @method static \PdfGate\Dto\PdfGateDocumentMetadata generatePdf(GeneratePdfRequestPayload $request)
 * @method static \PdfGate\Dto\PdfGateDocumentMetadata uploadFile(UploadFileRequestPayload $request)
 * @method static \PdfGate\Dto\PdfGateDocumentMetadata flattenPdf(FlattenPdfRequestPayload $request)
 * @method static \PdfGate\Dto\PdfGateDocumentMetadata addFormFields(AddFormFieldsRequestPayload $request)
 * @method static \PdfGate\Dto\PdfGateDocumentMetadata compressPdf(CompressPdfRequestPayload $request)
 * @method static \PdfGate\Dto\PdfGateDocumentMetadata protectPdf(ProtectPdfRequestPayload $request)
 * @method static \PdfGate\Dto\PdfGateDocumentMetadata watermarkPdf(WatermarkPdfRequestPayload $request)
 * @method static array<string,mixed> extractPdfFormData(ExtractPdfFormDataRequestPayload $request)
 * @method static \PdfGate\Dto\PdfGateDocumentMetadata getDocument(string $documentId, GetDocumentQueryPayload $query = [])
 * @method static void deleteDocument(string $documentId)
 * @method static resource getFile(string $documentId)
 * @method static \PdfGate\Dto\PdfGateEnvelope createEnvelope(CreateEnvelopeRequestPayload $request)
 * @method static \PdfGate\Dto\PdfGateEnvelope sendEnvelope(string $envelopeId)
 * @method static \PdfGate\Dto\PdfGateEnvelope getEnvelope(string $envelopeId)
 * @method static \PdfGate\Dto\PdfGateEnvelope voidEnvelope(string $envelopeId, ?string $reason = null)
 * @method static void deleteEnvelope(string $envelopeId)
 * @method static \PdfGate\Dto\EmbedLinkResponse createEmbedLink(string $envelopeId, CreateEmbedLinkRequestPayload $request)
 * @method static \PdfGate\Dto\PdfGateRecipient createRecipient(CreateRecipientRequestPayload $request)
 * @method static list<\PdfGate\Dto\PdfGateRecipient> listRecipients(string $email)
 * @method static \PdfGate\Dto\PdfGateRecipient getRecipient(string $recipientId)
 * @method static \PdfGate\Dto\PdfGateRecipient updateRecipient(string $recipientId, UpdateRecipientRequestPayload $request)
 * @method static \PdfGate\Dto\WebhookResponse createWebhook(CreateWebhookRequestPayload $request)
 * @method static \PdfGate\Dto\WebhookResponse getWebhook(string $webhookId)
 * @method static void deleteWebhook(string $webhookId)
 *
 * @see PdfGateManager
 */
final class PdfGate extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PdfGateManager::class;
    }
}

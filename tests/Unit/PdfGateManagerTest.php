<?php

declare(strict_types=1);

namespace PdfGate\Laravel\Tests\Unit;

use Illuminate\Support\Facades\Storage;
use PdfGate\Dto\PdfGateDocumentMetadata;
use PdfGate\Dto\PdfGateRecipient;
use PdfGate\Http\HttpResponse;
use PdfGate\Laravel\Facades\PdfGate;
use PdfGate\Laravel\Tests\TestCase;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PdfGateManagerTest extends TestCase
{
    private const PDF = "%PDF-1.4\n%fake\n";

    public function testFacadeForwardsClientMethods(): void
    {
        $this->transport->queueJson([
            'id' => 'doc_1',
            'status' => 'completed',
            'size' => 123,
            'createdAt' => '2026-10-27T12:00:00.000Z',
        ]);

        $document = PdfGate::generatePdf(['html' => '<h1>Hi</h1>']);

        $this->assertInstanceOf(PdfGateDocumentMetadata::class, $document);
        $this->assertSame('doc_1', $document->getId());
        $this->assertCount(1, $this->transport->requests);
        $this->assertSame('https://api-sandbox.pdfgate.com/v1/generate/pdf', $this->transport->requests[0]->getUrl());
        $this->assertSame(['html' => '<h1>Hi</h1>', 'jsonResponse' => true], $this->transport->requests[0]->getJsonBody());
    }

    public function testFacadeForwardsAutomaticSigningMethods(): void
    {
        $this->transport->queueJson([
            'id' => 'rec_1',
            'email' => 'ceo@example.com',
            'createdAt' => '2026-10-27T12:00:00.000Z',
            'signature' => ['status' => 'pending', 'requestedAt' => '2026-11-01T08:17:47.782Z', 'useCount' => 0],
        ], 201);
        $this->transport->queueJson([
            'id' => 'rec_1',
            'email' => 'ceo@example.com',
            'createdAt' => '2026-10-27T12:00:00.000Z',
            'signature' => ['status' => 'revoked', 'revokedAt' => '2026-11-01T08:30:00.000Z', 'useCount' => 0],
        ]);
        $this->transport->queue(new HttpResponse(204, '', []));

        $pending = PdfGate::requestRecipientSignature('rec_1');
        $revoked = PdfGate::removeRecipientSignature('rec_1');
        PdfGate::deleteRecipient('rec_1');

        $this->assertInstanceOf(PdfGateRecipient::class, $pending);
        $this->assertSame('pending', $pending->getSignature()?->getStatus());
        $this->assertSame('revoked', $revoked->getSignature()?->getStatus());
        $this->assertCount(3, $this->transport->requests);
        $this->assertSame('POST', $this->transport->requests[0]->getMethod());
        $this->assertSame('https://api-sandbox.pdfgate.com/recipient/rec_1/signature/request', $this->transport->requests[0]->getUrl());
        $this->assertSame('DELETE', $this->transport->requests[1]->getMethod());
        $this->assertSame('https://api-sandbox.pdfgate.com/recipient/rec_1/signature', $this->transport->requests[1]->getUrl());
        $this->assertSame('DELETE', $this->transport->requests[2]->getMethod());
        $this->assertSame('https://api-sandbox.pdfgate.com/recipient/rec_1', $this->transport->requests[2]->getUrl());
    }

    public function testDownloadStreamsPdfAsAttachment(): void
    {
        $this->queuePdf();

        $response = PdfGate::download('doc_1', 'invoice.pdf');

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertSame('attachment; filename=invoice.pdf', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::PDF, $this->capture($response));
        $this->assertSame('https://api-sandbox.pdfgate.com/file/doc_1', $this->transport->requests[0]->getUrl());
    }

    public function testInlineStreamsPdfInline(): void
    {
        $this->queuePdf();

        $response = PdfGate::inline('doc_1', 'invoice.pdf');

        $this->assertSame('inline; filename=invoice.pdf', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::PDF, $this->capture($response));
    }

    public function testStoreWritesPdfToDisk(): void
    {
        $disk = Storage::fake('local');
        $this->queuePdf();

        $this->assertTrue(PdfGate::store('doc_1', 'invoices/doc_1.pdf', 'local'));

        $disk->assertExists('invoices/doc_1.pdf');
        $this->assertSame(self::PDF, $disk->get('invoices/doc_1.pdf'));
    }

    private function queuePdf(): void
    {
        $this->transport->queue(new HttpResponse(200, self::PDF, ['content-type' => 'application/pdf']));
    }

    private function capture(StreamedResponse $response): string
    {
        ob_start();
        $response->sendContent();

        return (string) ob_get_clean();
    }
}

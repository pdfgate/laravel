<?php

declare(strict_types=1);

namespace PdfGate\Laravel;

use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Routing\ResponseFactory;
use PdfGate\PdfGateClient;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Wraps the PDFGate client with Laravel response and storage helpers.
 *
 * Every PdfGateClient method is available on this class as well.
 *
 * @mixin PdfGateClient
 */
class PdfGateManager
{
    public function __construct(
        private readonly PdfGateClient $client,
        private readonly ResponseFactory $responses,
        private readonly FilesystemFactory $filesystems
    ) {
    }

    /**
     * The underlying SDK client.
     */
    public function client(): PdfGateClient
    {
        return $this->client;
    }

    /**
     * Streams a stored document to the browser as a file download.
     *
     * @param string $documentId Document ID returned by the API.
     * @param string $filename File name the browser saves the download as.
     */
    public function download(string $documentId, string $filename = 'document.pdf'): StreamedResponse
    {
        return $this->streamDocument($documentId, $filename, 'attachment');
    }

    /**
     * Streams a stored document to the browser for inline display.
     *
     * @param string $documentId Document ID returned by the API.
     * @param string $filename File name shown by the browser's PDF viewer.
     */
    public function inline(string $documentId, string $filename = 'document.pdf'): StreamedResponse
    {
        return $this->streamDocument($documentId, $filename, 'inline');
    }

    /**
     * Downloads a stored document and writes it to a filesystem disk.
     *
     * @param string $documentId Document ID returned by the API.
     * @param string $path Path on the disk, for example "invoices/123.pdf".
     * @param string|null $disk Disk name from config/filesystems.php; null uses the default disk.
     * @return bool True when the file was written.
     */
    public function store(string $documentId, string $path, ?string $disk = null): bool
    {
        $stream = $this->client->getFile($documentId);

        try {
            return (bool) $this->filesystems->disk($disk)->put($path, $stream);
        } finally {
            fclose($stream);
        }
    }

    /**
     * Forwards every other call to the SDK client.
     *
     * @param array<int,mixed> $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->client->{$method}(...$arguments);
    }

    private function streamDocument(string $documentId, string $filename, string $disposition): StreamedResponse
    {
        $stream = $this->client->getFile($documentId);

        return $this->responses->streamDownload(
            static function () use ($stream): void {
                fpassthru($stream);
                fclose($stream);
            },
            $filename,
            ['Content-Type' => 'application/pdf'],
            $disposition
        );
    }
}

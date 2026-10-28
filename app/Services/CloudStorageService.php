<?php

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;

class CloudStorageService
{
    private const API_URL = 'https://vercel.com/api/blob';
    private const API_VERSION = '13';

    public function store(UploadedFile $file, string $folder, ?string $filename = null): string
    {
        $folder = trim($folder, '/');
        $filename ??= $file->getRandomName();

        if (! $this->usesBlob()) {
            $directory = FCPATH . 'uploads/' . $folder . '/';
            if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                throw new RuntimeException('Unable to prepare the upload directory.');
            }
            $file->move($directory, $filename);

            return 'uploads/' . $folder . '/' . $filename;
        }

        $temporaryPath = $file->getTempName();
        $contents = file_get_contents($temporaryPath);
        if ($contents === false) {
            throw new RuntimeException('Unable to read the uploaded file.');
        }

        $pathname = $folder . '/' . $filename;
        $response = $this->request(
            'PUT',
            self::API_URL . '/?pathname=' . rawurlencode($pathname),
            $contents,
            [
                'Content-Type: application/octet-stream',
                'x-content-type: ' . ($file->getMimeType() ?: 'application/octet-stream'),
                'x-add-random-suffix: 0',
                'x-allow-overwrite: 0',
                'x-cache-control-max-age: 31536000',
                'x-vercel-blob-access: private',
            ],
        );
        $payload = json_decode($response, true);
        $url = is_array($payload) ? ($payload['url'] ?? null) : null;
        if (! is_string($url) || ! $this->isStoreUrl($url)) {
            throw new RuntimeException('Blob storage returned an invalid upload response.');
        }

        return 'media/' . $this->encodeUrl($url);
    }

    public function fetch(string $encodedUrl): array
    {
        $url = $this->decodeUrl($encodedUrl);
        if (! $this->isStoreUrl($url)) {
            throw new RuntimeException('Invalid media reference.');
        }

        $headers = [];
        $body = $this->request('GET', $url, null, [], $headers);

        return [
            'body' => $body,
            'contentType' => $headers['content-type'] ?? 'application/octet-stream',
            'pathname' => ltrim((string) parse_url($url, PHP_URL_PATH), '/'),
        ];
    }

    public function pathname(string $encodedUrl): string
    {
        $url = $this->decodeUrl($encodedUrl);
        if (! $this->isStoreUrl($url)) {
            throw new RuntimeException('Invalid media reference.');
        }

        return ltrim((string) parse_url($url, PHP_URL_PATH), '/');
    }

    public function delete(string $storedPath): void
    {
        if (! str_starts_with($storedPath, 'media/')) {
            $localPath = FCPATH . ltrim($storedPath, '/');
            if (is_file($localPath)) {
                @unlink($localPath);
            }
            return;
        }

        $url = $this->decodeUrl(substr($storedPath, strlen('media/')));
        if (! $this->isStoreUrl($url)) {
            return;
        }

        $this->request(
            'POST',
            self::API_URL . '/delete',
            json_encode(['urls' => [$url]], JSON_THROW_ON_ERROR),
            ['Content-Type: application/json'],
        );
    }

    private function usesBlob(): bool
    {
        return (getenv('VERCEL') !== false || getenv('APP_RUNTIME') === 'vercel')
            && trim((string) getenv('BLOB_READ_WRITE_TOKEN')) !== '';
    }

    private function request(string $method, string $url, ?string $body = null, array $headers = [], ?array &$responseHeaders = null): string
    {
        $token = trim((string) getenv('BLOB_READ_WRITE_TOKEN'));
        if ($token === '') {
            throw new RuntimeException('Blob storage is not configured.');
        }

        $parts = explode('_', $token);
        $storeId = $parts[3] ?? '';
        if ($storeId === '') {
            throw new RuntimeException('Blob storage credentials are invalid.');
        }

        $capturedHeaders = [];
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 50,
            CURLOPT_HTTPHEADER => array_merge([
                'Authorization: Bearer ' . $token,
                'x-api-version: ' . self::API_VERSION,
                'x-vercel-blob-store-id: ' . $storeId,
            ], $headers),
            CURLOPT_HEADERFUNCTION => static function ($curl, string $header) use (&$capturedHeaders): int {
                $length = strlen($header);
                $position = strpos($header, ':');
                if ($position !== false) {
                    $capturedHeaders[strtolower(trim(substr($header, 0, $position)))] = trim(substr($header, $position + 1));
                }
                return $length;
            },
        ]);
        if ($body !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        }

        $result = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if (! is_string($result) || $status < 200 || $status >= 300) {
            throw new RuntimeException('Blob storage request failed' . ($error !== '' ? ': ' . $error : " (HTTP {$status})"));
        }
        $responseHeaders = $capturedHeaders;

        return $result;
    }

    private function encodeUrl(string $url): string
    {
        return rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
    }

    private function decodeUrl(string $encodedUrl): string
    {
        $padding = strlen($encodedUrl) % 4;
        if ($padding !== 0) {
            $encodedUrl .= str_repeat('=', 4 - $padding);
        }
        $decoded = base64_decode(strtr($encodedUrl, '-_', '+/'), true);

        return is_string($decoded) ? $decoded : '';
    }

    private function isStoreUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ((string) parse_url($url, PHP_URL_SCHEME) !== 'https'
            || ! str_ends_with($host, '.private.blob.vercel-storage.com')) {
            return false;
        }

        $tokenParts = explode('_', trim((string) getenv('BLOB_READ_WRITE_TOKEN')));
        $storeId = strtolower($tokenParts[3] ?? '');

        return $storeId !== '' && str_starts_with($host, $storeId . '.');
    }
}

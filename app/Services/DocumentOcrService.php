<?php

namespace App\Services;

/**
 * API-based preliminary document screening. OCR extracts visible text only;
 * final confirmation of a document remains with the administrator.
 */
class DocumentOcrService
{
    public function screen(string $imagePath, string $documentType, string $applicantName): array
    {
        if (in_array($documentType, ['selfie', 'home_photos'], true)) {
            return $this->passed('Live camera, GPS, timestamp, image-format, and image-quality checks passed; OCR is not required for this photo type.');
        }

        $apiKey = trim((string) env('ocr.apiKey'));
        if ($apiKey === '') {
            return $this->needsReview('OCR screening is temporarily unavailable. The administrator must review this live-captured document.');
        }

        $endpoint = trim((string) env('ocr.endpoint', 'https://api.ocr.space/parse/image'));
        $ocrImagePath = $this->prepareForOcr($imagePath);
        $curl = curl_init($endpoint);
        if ($curl === false) {
            if ($ocrImagePath !== $imagePath) @unlink($ocrImagePath);
            return $this->needsReview('The document could not be screened automatically and requires administrator review.');
        }

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'file' => new \CURLFile($ocrImagePath, 'image/jpeg', $documentType . '.jpg'),
                'language' => 'auto',
                'isOverlayRequired' => 'false',
                'OCREngine' => '2',
                // OCR.space corrects rotated camera frames itself. Scaling is
                // useful for small text on a physical Philippine ID card.
                'detectOrientation' => 'true',
                'scale' => 'true',
            ],
            CURLOPT_HTTPHEADER => ['apikey: ' . $apiKey],
            CURLOPT_RETURNTRANSFER => true,
            // Keep the web request responsive. A slow/unavailable OCR provider
            // must never hold an adopter on a loading screen for minutes.
            CURLOPT_TIMEOUT_MS => 5000,
            CURLOPT_CONNECTTIMEOUT_MS => 2000,
            CURLOPT_NOSIGNAL => true,
        ]);
        $body = curl_exec($curl);
        $error = curl_error($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        if ($ocrImagePath !== $imagePath) @unlink($ocrImagePath);

        if ($body === false || $httpCode < 200 || $httpCode >= 300) {
            log_message('error', 'OCR request failed: {error}; HTTP {status}.', ['error' => $error ?: 'No response body', 'status' => $httpCode]);
            return $this->needsReview('OCR could not read this photo reliably. The administrator will review the live-captured document.');
        }

        $response = json_decode($body, true);
        if (! is_array($response) || ! empty($response['IsErroredOnProcessing'])) {
            log_message('error', 'OCR processing failed: {error}.', ['error' => json_encode($response['ErrorMessage'] ?? 'Invalid response')]);
            return $this->needsReview('OCR could not read this document reliably. The administrator will review the live-captured document.');
        }

        $text = trim(implode("\n", array_filter(array_map(
            static fn (array $result): string => (string) ($result['ParsedText'] ?? ''),
            (array) ($response['ParsedResults'] ?? [])
        ))));

        $hasUsableText = $this->hasUsableText($text);
        $nameMatches = $documentType !== 'gov_id' || $this->nameMatches($applicantName, $text);
        $hasIdMarker = $documentType !== 'gov_id' || $this->hasGovernmentIdMarker($text);

        if (! $hasUsableText) {
            return $this->failed($documentType === 'gov_id'
                ? 'The Government ID has no usable readable text. Capture the actual ID clearly and try again.'
                : 'The proof-of-address document has no usable readable text. Capture the actual document clearly and try again.');
        }
        if ($documentType === 'gov_id' && ! $hasIdMarker) {
            return $this->failed('The photo does not appear to be a supported Government ID. Capture your PhilHealth, passport, driver’s license, or national ID.');
        }
        if ($documentType === 'gov_id' && ! $nameMatches) {
            return $this->failed('The name on the Government ID does not match the Full Name entered in the application.');
        }

        return $this->passed($documentType === 'gov_id'
            ? 'Readable identification text and applicant-name match found.'
            : 'Readable document text found.');
    }

    private function hasUsableText(string $text): bool
    {
        preg_match_all('/[A-Za-z0-9]{2,}/', $text, $words);
        return mb_strlen(preg_replace('/\s+/', '', $text) ?? '') >= 12 && count($words[0]) >= 2;
    }

    /** Keeps camera images within the hosted OCR service's small upload limit. */
    private function prepareForOcr(string $imagePath): string
    {
        if (filesize($imagePath) <= 900 * 1024) return $imagePath;
        $image = @imagecreatefromjpeg($imagePath);
        if (! $image) return $imagePath;

        $temporaryPath = tempnam(sys_get_temp_dir(), 'catcare-ocr-');
        if ($temporaryPath === false) {
            imagedestroy($image);
            return $imagePath;
        }
        for ($quality = 85; $quality >= 45; $quality -= 10) {
            imagejpeg($image, $temporaryPath, $quality);
            if (filesize($temporaryPath) <= 900 * 1024) break;
        }
        imagedestroy($image);
        return $temporaryPath;
    }

    private function nameMatches(string $applicantName, string $documentText): bool
    {
        $normalize = static function (string $value): string {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
            return preg_replace('/[^a-z]/', '', strtolower($value)) ?? '';
        };
        $nameParts = preg_split('/\s+/', trim($applicantName)) ?: [];
        $nameParts = array_values(array_filter(array_map($normalize, $nameParts), static fn (string $part): bool => strlen($part) >= 2));
        // Require first name, last name, and at least one middle-name part.
        // This prevents a shortened name (for example, first + last only)
        // from passing when the ID shows the full legal name.
        if (count($nameParts) < 3) return false;

        $documentWords = array_flip(array_filter(array_map($normalize, preg_split('/[^A-Za-z]+/', $documentText) ?: [])));
        $matched = count(array_filter($nameParts, static fn (string $part): bool => isset($documentWords[$part])));

        // Exact words only; punctuation, capitalization, and name-word order
        // are ignored, but a different or misspelled name cannot pass.
        return $matched === count($nameParts);
    }

    private function hasGovernmentIdMarker(string $documentText): bool
    {
        return (bool) preg_match('/\b(philhealth|passport|driver[\s\'’]*s?\s*license|national\s*id|philippine\s*identification|pag-ibig|umid)\b/i', $documentText);
    }

    private function passed(string $message): array
    {
        return ['passed' => true, 'status' => 'Passed', 'message' => $message];
    }

    private function failed(string $message): array
    {
        return ['passed' => false, 'status' => 'Failed', 'message' => $message];
    }

    private function needsReview(string $message): array
    {
        return ['passed' => true, 'status' => 'Needs Review', 'message' => $message];
    }
}

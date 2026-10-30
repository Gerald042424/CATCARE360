<?php

namespace App\Services;

/**
 * API-based preliminary document screening. OCR extracts visible text only;
 * final confirmation of a document remains with the administrator.
 */
class DocumentOcrService
{
    public function screen(string $imagePath, string $documentType, string $applicantName, ?string $expectedIdType = null): array
    {
        $apiKey = trim((string) env('ocr.apiKey'));
        if ($apiKey === '') {
            return $this->failed('Government ID and proof of address verification is temporarily unavailable. Please try again shortly.');
        }

        $endpoint = trim((string) env('ocr.endpoint', 'https://api.ocr.space/parse/image'));
        $ocrImagePath = $this->prepareForOcr($imagePath);
        $curl = curl_init($endpoint);
        if ($curl === false) {
            if ($ocrImagePath !== $imagePath) @unlink($ocrImagePath);
            return $this->failed('The document verification service is temporarily unavailable. Please try again shortly.');
        }

        // XAMPP commonly ships without a configured CA bundle. Use its bundled
        // Composer CA file when present so HTTPS remains verified; never turn
        // off TLS verification just to make OCR work.
        $caBundle = (string) env('ocr.caBundle', 'C:/xampp/phpMyAdmin/vendor/composer/ca-bundle/res/cacert.pem');
        $options = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'file' => new \CURLFile($ocrImagePath, 'image/jpeg', $documentType . '.jpg'),
                'language' => 'eng',
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
            CURLOPT_TIMEOUT_MS => 15000,
            CURLOPT_CONNECTTIMEOUT_MS => 5000,
            CURLOPT_NOSIGNAL => true,
        ];
        if (is_file($caBundle)) $options[CURLOPT_CAINFO] = $caBundle;
        curl_setopt_array($curl, $options);
        $body = curl_exec($curl);
        $error = curl_error($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        if ($ocrImagePath !== $imagePath) @unlink($ocrImagePath);

        if ($body === false || $httpCode < 200 || $httpCode >= 300) {
            log_message('error', 'OCR request failed: {error}; HTTP {status}.', ['error' => $error ?: 'No response body', 'status' => $httpCode]);
            return $this->failed('OCR could not read the document. Retake it in brighter light with all text fully visible.');
        }

        $response = json_decode($body, true);
        if (! is_array($response) || ! empty($response['IsErroredOnProcessing'])) {
            log_message('error', 'OCR processing failed: {error}.', ['error' => json_encode($response['ErrorMessage'] ?? 'Invalid response')]);
            return $this->failed('OCR could not read the document. Retake it in brighter light with all text fully visible.');
        }

        $text = trim(implode("\n", array_filter(array_map(
            static fn (array $result): string => (string) ($result['ParsedText'] ?? ''),
            (array) ($response['ParsedResults'] ?? [])
        ))));

        $hasUsableText = $this->hasUsableText($text);
        $nameMatches = in_array($documentType, ['gov_id', 'selfie'], true) ? $this->nameMatches($applicantName, $text) : true;

        if (! $hasUsableText) {
            if ($documentType === 'gov_id') return $this->failed('The Government ID has no usable readable text. Capture the actual ID clearly and try again.');
            if ($documentType === 'selfie') return $this->failed('The selfie with Government ID must clearly show your face and readable ID text. Capture both in good lighting.');
            if ($documentType === 'address_proof') return $this->failed('The proof-of-address document has no usable readable text. Capture the actual document clearly and try again.');
            return $this->failed('The home photo is too unclear to verify. Capture a well-lit photo of the cat living space.');
        }
        if ($documentType === 'gov_id') {
            $allowedIdTypes = ['philhealth', 'national_id', 'drivers_license', 'passport'];
            $detectedIdType = $this->detectGovernmentIdType($text);
            if (! in_array((string) $expectedIdType, $allowedIdTypes, true)) {
                return $this->failed('Select and scan the Government ID type again before submitting.');
            }
            if ($detectedIdType === null) {
                return $this->failed('The selected Government ID format could not be confirmed from the captured photo. Retake it with the ID title and all text clearly visible.');
            }
            if ($detectedIdType !== $expectedIdType) {
                return $this->failed('The captured Government ID does not match the selected ID type. Select the correct type and scan it again.');
            }
        }
        if ($documentType === 'gov_id' && ! $nameMatches) {
            return $this->failed('The name on the Government ID does not match the Full Name entered in the application.');
        }
        if ($documentType === 'selfie' && ! $nameMatches) {
            return $this->failed('The readable name on the ID in the selfie does not match the Full Name entered in the application.');
        }

        if ($documentType === 'gov_id') return $this->passed('Readable identification text and applicant-name match found.');
        if ($documentType === 'selfie') return $this->passed('Readable ID text and applicant-name match found in the selfie capture.');
        if ($documentType === 'address_proof') return $this->passed('Readable proof-of-address text found.');
        return $this->passed('Home photo passed image-quality and text-presence checks.');
    }

    private function hasUsableText(string $text): bool
    {
        preg_match_all('/[A-Za-z0-9]{2,}/', $text, $words);
        return mb_strlen(preg_replace('/\s+/', '', $text) ?? '') >= 5 && count($words[0]) >= 1;
    }

    private function detectGovernmentIdType(string $text): ?string
    {
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9<]+/', ' ', $normalized) ?? '');

        if (preg_match('/PHILHEALTH|PHILIPPINE\s+HEALTH\s+INSURANCE/', $normalized)) return 'philhealth';
        if (preg_match('/PHILIPPINE\s+IDENTIFICATION|PAMBANSANG\s+PAGKAKAKILANLAN|PHILSYS/', $normalized)) return 'national_id';
        if (preg_match('/DRIVER\s*S\s+LICENSE|LAND\s+TRANSPORTATION\s+OFFICE|\bLTO\b/', $normalized)) return 'drivers_license';
        if ((preg_match('/PASSPORT/', $normalized) && preg_match('/PILIPINAS|PHILIPPINES|\bPHL\b/', $normalized)) || preg_match('/P<PHL/', $normalized)) return 'passport';

        return null;
    }

    private function hasGovernmentIdMarker(string $text): bool
    {
        return $this->detectGovernmentIdType($text) !== null;
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
        // Match the entered first and last name. IDs often show a middle
        // initial, omit a middle name, or place the surname first, so making
        // every middle-name token mandatory produces false rejections.
        if (count($nameParts) < 2) return false;

        $documentWords = array_flip(array_filter(array_map($normalize, preg_split('/[^A-Za-z]+/', $documentText) ?: [])));
        $firstName = $nameParts[0];
        $lastName = $nameParts[count($nameParts) - 1];

        // Exact words only; punctuation, capitalization, and name-word order
        // are ignored, but a different first or last name cannot pass.
        return isset($documentWords[$firstName], $documentWords[$lastName]);
    }

    private function passed(string $message): array
    {
        return ['passed' => true, 'status' => 'Passed', 'message' => $message];
    }

    private function failed(string $message): array
    {
        return ['passed' => false, 'status' => 'Failed', 'message' => $message];
    }


}

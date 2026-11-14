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
        $ocr = $this->readText($imagePath, $documentType);
        if (! $ocr['passed']) return $ocr;
        $text = $ocr['text'];

        $hasUsableText = $this->hasUsableText($text);
        $nameMatches = in_array($documentType, ['gov_id', 'selfie'], true) ? $this->nameMatches($applicantName, $text) : true;

        if (! $hasUsableText) {
            if ($documentType === 'gov_id') return $this->failed('The valid government-issued ID has no usable readable text. Capture the original ID clearly and try again.');
            if ($documentType === 'selfie') return $this->failed('The selfie with Government ID must clearly show your face and readable ID text. Capture both in good lighting.');
            if ($documentType === 'address_proof') return $this->failed('The proof-of-address document has no usable readable text. Capture the actual document clearly and try again.');
            return $this->failed('The home photo is too unclear to verify. Capture a well-lit photo of the cat living space.');
        }
        if ($documentType === 'gov_id') {
            $allowedIdTypes = ['philhealth', 'national_id', 'drivers_license', 'passport', 'prc_id'];
            $detectedIdType = $this->detectGovernmentIdType($text);
            if (! in_array((string) $expectedIdType, $allowedIdTypes, true)) {
                return $this->failed('Select and scan the valid government-issued ID type again before submitting.');
            }
            if ($detectedIdType === null) {
                return $this->failed('The selected valid government-issued ID type could not be confirmed from the captured photo. Retake it with the ID title and all text clearly visible.');
            }
            if ($detectedIdType !== $expectedIdType) {
                return $this->failed('The captured valid government-issued ID does not match the selected ID type. Select the correct type and scan it again.');
            }
        }
        if ($documentType === 'gov_id' && ! $nameMatches) {
            return $this->failed('The name on the valid government-issued ID does not match the Full Name entered in the application.');
        }
        if ($documentType === 'selfie' && ! $nameMatches) {
            return $this->failed('The readable name on the ID in the selfie does not match the Full Name entered in the application.');
        }

        if ($documentType === 'gov_id') return $this->passed('Preliminary document-type and applicant-name match found; final authenticity requires staff review.', ['ocr_text' => $text, 'detected_id_type' => $this->detectGovernmentIdType($text)]);
        if ($documentType === 'selfie') return $this->passed('Readable ID text and applicant-name match found in the selfie capture.');
        if ($documentType === 'address_proof') return $this->passed('Readable proof-of-address text found.');
        return $this->passed('Home photo passed image-quality and text-presence checks.');
    }

    public function screenDigitalEvidence(string $imagePath, string $physicalIdText, string $applicantName, string $expectedIdType): array
    {
        $supportedTypes = ['philhealth', 'national_id', 'drivers_license', 'prc_id'];
        if (! in_array($expectedIdType, $supportedTypes, true)) {
            return $this->failed('This ID type does not have a supported digital-evidence source. It requires staff review.');
        }

        $ocr = $this->readText($imagePath, 'digital_id_evidence');
        if (! $ocr['passed']) return $ocr;
        $evidenceText = (string) $ocr['text'];
        if (! $this->hasUsableText($evidenceText)) {
            return $this->failed('The uploaded government-app screenshot has no readable text. Upload a clear, uncropped screenshot.');
        }

        $source = $this->detectDigitalEvidenceSource($evidenceText, $expectedIdType);
        if ($source === null) {
            return $this->failed('The screenshot does not show the expected official eGovPH, LTO/LTMS, or PRC verification source for the selected ID.');
        }
        if ($this->detectGovernmentIdType($evidenceText) !== $expectedIdType) {
            return $this->failed('The ID shown in the government-app screenshot does not match the selected and scanned ID type.');
        }
        if (! $this->nameMatches($applicantName, $evidenceText)) {
            return $this->failed('The name in the government-app screenshot does not match the applicant’s full name.');
        }

        $numberMatchQuality = $this->matchDocumentNumbers($physicalIdText, $evidenceText, $expectedIdType);
        $numberMatched = $numberMatchQuality !== null;

        $sharedBirthDate = $this->sharedDate($physicalIdText, $evidenceText);
        if (! $numberMatched && $sharedBirthDate === null) {
            return $this->failed('The official source, ID type, and applicant name were found, but the document number or birth date could not be matched reliably. Upload the complete original screenshot without cropping or editing.');
        }

        return $this->passed('The official-source markers and identity information matched the live-scanned ID. Final authenticity remains subject to staff review.', [
            'source' => $source,
            'match_basis' => $numberMatched ? 'name_and_document_number' : 'name_and_birth_date',
            'number_match' => $numberMatched,
            'number_match_quality' => $numberMatchQuality,
            'birth_date_match' => $sharedBirthDate !== null,
        ]);
    }

    private function detectDigitalEvidenceSource(string $text, string $expectedIdType): ?string
    {
        $normalized = strtoupper(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text);
        $hasEgov = preg_match('/\bE\s*GOV\s*PH\b|\bEGOVPH\b|EGOV\s+SUPER\s+APP|DIGITAL\s+ID/', $normalized) === 1;
        $hasLto = preg_match('/LAND\s+TRANSPORTATION\s+OFFICE|\bLTO\b|\bLTMS\b|LAND\s+TRANSPORTATION\s+MANAGEMENT\s+SYSTEM/', $normalized) === 1;
        $hasPrc = preg_match('/PROFESSIONAL\s+REGULATION\s+COMMISSION|PROFESSIONAL\s+IDENTIFICATION\s+CARD|\bE\s*PIC\b|\bLERIS\b|ONLINE\s+VERIFICATION/', $normalized) === 1;

        if ($expectedIdType === 'prc_id') return $hasPrc ? 'prc_e_pic_or_leris' : null;
        if ($expectedIdType === 'drivers_license') {
            if ($hasEgov) return 'egovph';
            return $hasLto ? 'lto_ltms' : null;
        }
        return $hasEgov ? 'egovph' : null;
    }

    private function extractDocumentNumber(string $text, string $type): ?string
    {
        return $this->documentNumberCandidates($text, $type)[0] ?? null;
    }

    private function documentNumberCandidates(string $text, string $type): array
    {
        $normalized = strtoupper(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text);
        $ocrDigit = '0-9OQILZSBG';
        $patterns = [
            'philhealth' => '/\b[' . $ocrDigit . ']{2}[-\s.]?[' . $ocrDigit . ']{9}[-\s.]?[' . $ocrDigit . ']\b/',
            'national_id' => '/\b[' . $ocrDigit . ']{4}(?:[-\s.]?[' . $ocrDigit . ']{4}){3}\b/',
            'drivers_license' => '/\b[A-Z][' . $ocrDigit . ']{2}[-\s.]?[' . $ocrDigit . ']{2}[-\s.]?[' . $ocrDigit . ']{6}\b/',
        ];
        $matches = [0 => []];
        if (isset($patterns[$type])) preg_match_all($patterns[$type], $normalized, $matches);
        if ($type === 'prc_id') {
            $lines = preg_split('/\R/', $normalized) ?: [];
            foreach ($lines as $index => $line) {
                if (! preg_match('/REGISTRATION\s*(?:NO|NUMBER)|LICENSE\s*(?:NO|NUMBER)/', $line)) continue;
                preg_match_all('/\b[' . $ocrDigit . ']{5,8}\b/', $line . "\n" . ($lines[$index + 1] ?? ''), $lineMatches);
                array_push($matches[0], ...($lineMatches[0] ?? []));
            }
        }

        $candidates = [];
        foreach ($matches[0] ?? [] as $match) {
            $compact = preg_replace('/[^A-Z0-9]/', '', strtoupper($match)) ?? '';
            $prefix = $type === 'drivers_license' ? substr($compact, 0, 1) : '';
            $digits = strtr($type === 'drivers_license' ? substr($compact, 1) : $compact, [
                'O' => '0', 'Q' => '0', 'I' => '1', 'L' => '1', 'Z' => '2',
                'S' => '5', 'G' => '6', 'B' => '8',
            ]);
            $value = $prefix . preg_replace('/\D/', '', $digits);
            $expectedLength = ['philhealth' => 12, 'national_id' => 16, 'drivers_license' => 11][$type] ?? null;
            if (($expectedLength !== null && strlen($value) === $expectedLength)
                || ($type === 'prc_id' && strlen($value) >= 5 && strlen($value) <= 8)) {
                $candidates[] = $value;
            }
        }
        return array_values(array_unique($candidates));
    }

    private function matchDocumentNumbers(string $physicalText, string $evidenceText, string $type): ?string
    {
        $physicalCandidates = $this->documentNumberCandidates($physicalText, $type);
        $evidenceCandidates = $this->documentNumberCandidates($evidenceText, $type);
        foreach ($physicalCandidates as $physical) {
            foreach ($evidenceCandidates as $evidence) {
                if (hash_equals($physical, $evidence)) return 'exact';
                if (strlen($physical) !== strlen($evidence) || strlen($physical) < 7) continue;
                $distance = levenshtein($physical, $evidence);
                if ($distance <= 1) return 'ocr_tolerant';
                if (strlen($physical) >= 10 && substr($physical, -6) === substr($evidence, -6) && $distance <= 2) return 'ocr_tolerant';
            }
        }
        return null;
    }

    private function sharedDate(string $firstText, string $secondText): ?string
    {
        $extract = static function (string $text): array {
            preg_match_all('/\b(?:\d{4}[\/.-]\d{1,2}[\/.-]\d{1,2}|\d{1,2}[\/.-]\d{1,2}[\/.-]\d{2,4}|(?:JAN(?:UARY)?|FEB(?:RUARY)?|MAR(?:CH)?|APR(?:IL)?|MAY|JUN(?:E)?|JUL(?:Y)?|AUG(?:UST)?|SEP(?:T(?:EMBER)?)?|OCT(?:OBER)?|NOV(?:EMBER)?|DEC(?:EMBER)?)\s+\d{1,2},?\s+\d{4})\b/i', $text, $matches);
            $dates = [];
            foreach ($matches[0] ?? [] as $value) {
                $timestamp = strtotime($value);
                if ($timestamp !== false) $dates[] = date('Y-m-d', $timestamp);
            }
            return array_values(array_unique($dates));
        };
        $shared = array_values(array_intersect($extract($firstText), $extract($secondText)));
        return count($shared) === 1 ? $shared[0] : null;
    }

    private function readText(string $imagePath, string $documentType): array
    {
        $apiKey = trim((string) (getenv('OCR_API_KEY') ?: env('ocr.apiKey')));
        if ($apiKey === '') {
            return $this->failed('Document verification is temporarily unavailable. Please try again shortly.');
        }

        $endpoint = trim((string) (getenv('OCR_ENDPOINT') ?: env('ocr.endpoint', 'https://api.ocr.space/parse/image')));
        $ocrImagePath = $this->prepareForOcr($imagePath);
        $curl = curl_init($endpoint);
        if ($curl === false) {
            if ($ocrImagePath !== $imagePath) @unlink($ocrImagePath);
            return $this->failed('The document verification service is temporarily unavailable. Please try again shortly.');
        }

        $caBundle = (string) env('ocr.caBundle', 'C:/xampp/phpMyAdmin/vendor/composer/ca-bundle/res/cacert.pem');
        $options = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'file' => new \CURLFile($ocrImagePath, 'image/jpeg', $documentType . '.jpg'),
                'language' => 'eng',
                'isOverlayRequired' => 'false',
                'OCREngine' => '2',
                'detectOrientation' => 'true',
                'scale' => 'true',
            ],
            CURLOPT_HTTPHEADER => ['apikey: ' . $apiKey],
            CURLOPT_RETURNTRANSFER => true,
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
            return $this->failed('OCR could not read the document. Retake or upload it with all text fully visible.');
        }

        $response = json_decode($body, true);
        if (! is_array($response) || ! empty($response['IsErroredOnProcessing'])) {
            log_message('error', 'OCR processing failed: {error}.', ['error' => json_encode($response['ErrorMessage'] ?? 'Invalid response')]);
            return $this->failed('OCR could not read the document. Retake or upload it with all text fully visible.');
        }

        $text = trim(implode("\n", array_filter(array_map(
            static fn (array $result): string => (string) ($result['ParsedText'] ?? ''),
            (array) ($response['ParsedResults'] ?? [])
        ))));

        return ['passed' => true, 'status' => 'Passed', 'message' => 'OCR completed.', 'text' => $text];
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
        $compactLetters = preg_replace('/[^A-Z]/', '', $normalized) ?? '';

        if (preg_match('/PROFESSIONAL\s+IDENTIFICATION\s+CARD|PROFESSIONAL\s+REGULATION\s+COMMISSION|\bPRC\b.*\b(?:E\s*PIC|LERIS)\b/', $normalized)) return 'prc_id';
        if (str_contains($compactLetters, 'PHILHEAL') || preg_match('/PHILIPPINE\s+HEALTH\s+INSURANCE/', $normalized)) return 'philhealth';
        if (preg_match('/PHILIPPINE\s+IDENTIFICATION|PAMBANSANG\s+PAGKAKAKILANLAN|PHILSYS|\bNATIONAL\s+ID\b/', $normalized)) return 'national_id';
        if (preg_match('/DRIVER\s*S\s+LICENSE|LAND\s+TRANSPORTATION\s+OFFICE|\bLTO\b/', $normalized)) return 'drivers_license';
        if ((preg_match('/PASSPOR[T7]?/', $normalized) && preg_match('/PILIPINAS|PHILIPPINES|\bPHL\b/', $normalized)) || preg_match('/P\s*<\s*PH[L1I]/', $normalized)) return 'passport';

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
        $contents = @file_get_contents($imagePath);
        $image = is_string($contents) ? @imagecreatefromstring($contents) : false;
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

        // Server-side OCR remains exact: the tolerant browser pass only helps
        // the applicant capture a readable image and cannot weaken submission.
        return isset($documentWords[$firstName], $documentWords[$lastName]);
    }

    private function passed(string $message, array $extra = []): array
    {
        return array_merge(['passed' => true, 'status' => 'Passed', 'message' => $message], $extra);
    }

    private function failed(string $message): array
    {
        return ['passed' => false, 'status' => 'Failed', 'message' => $message];
    }


}

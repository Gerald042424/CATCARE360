<?php

namespace App\Controllers\Adopter;

use App\Controllers\BaseController;
use App\Models\AdopterModel;
use App\Models\AdoptionApplicationModel;
use App\Models\AnimalModel;
use App\Models\ApplicationCatModel;
use App\Services\DocumentOcrService;
use App\Services\AdoptionAssessmentService;
use App\Models\AdoptionAssessmentFlagModel;
use App\Services\CloudStorageService;

class ApplicationController extends BaseController
{
    private const DOCUMENT_FIELDS = ['selfie', 'gov_id', 'address_proof', 'home_photos'];

    public function index(int $catId = 0)
    {
        $cat = null;
        if ($catId > 0) {
            $cat = (new AnimalModel())->where('id', $catId)->where('status', 'Available')->first();
            if (! $cat) {
                return redirect()->to('/adopter/home')->with('error', 'That cat is no longer available for adoption.');
            }
        }

        $adopter = (new AdopterModel())->find((int) session('adopter_user.adopter_id'));
        // The application contains browser-side camera state. Never let Chrome
        // restore an outdated version of this page from its cache.
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->response->setHeader('Pragma', 'no-cache');
        return view('adopter/application_form_live', [
            'title' => 'Adoption Application',
            'cat' => $cat,
            'catId' => $catId,
            'adopter' => $adopter,
            'availableCats' => (new AnimalModel())->where('status', 'Available')->orderBy('name', 'ASC')->findAll(),
            'assessmentGroups' => (new AdoptionAssessmentService())->groups(),
            'errors' => session('errors') ?? [],
        ]);
    }

    public function submit()
    {
        $adopterId = (int) session('adopter_user.adopter_id');
        if ($adopterId <= 0) {
            return redirect()->to('/')->with('error', 'Please sign in to submit an application.');
        }

        $rules = [
            'full_name' => 'required|max_length[150]',
            'address' => 'required|max_length[255]',
            'phone' => 'required|max_length[30]',
            'occupation' => 'required|max_length[100]',
        ];
        foreach ((new AdoptionAssessmentService())->groups() as $key => $_group) {
            $rules['assessment_' . $key . '_main'] = 'required|max_length[3000]';
            foreach ($_group['subquestions'] ?? [] as $index => $_subquestion) $rules['assessment_' . $key . '_sub_' . ($index + 1)] = 'required|max_length[2000]';
        }
        $post = $this->request->getPost();
        $catIds = array_values(array_unique(array_filter(array_map('intval', (array) ($post['cat_ids'] ?? [])))));
        $catId = $catIds[0] ?? 0;
        $cats = $catIds ? (new AnimalModel())->whereIn('id', $catIds)->where('status', 'Available')->findAll() : [];
        $cat = $cats[0] ?? null;
        if (! $catIds && ! trim((string) ($post['cat_description'] ?? ''))) {
            return redirect()->back()->withInput()->with('errors', ['cat_description' => 'Enter the cat name, color, or location.']);
        }
        if (count($cats) !== count($catIds)) {
            return redirect()->to('/adopter/home')->with('error', 'One or more selected cats are no longer available for adoption.');
        }
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $metadata = json_decode((string) ($post['verification_metadata'] ?? ''), true);
        if (! is_array($metadata)) {
            return redirect()->back()->withInput()->with('error', 'Capture all four live verification photos before submitting.');
        }
        $deviceInfo = [
            'user_agent' => substr($this->request->getUserAgent()->getAgentString(), 0, 500),
            'ip_address' => $this->request->getIPAddress(),
        ];
        foreach (self::DOCUMENT_FIELDS as $field) {
            if (isset($metadata[$field]) && is_array($metadata[$field])) {
                $metadata[$field]['device'] = $deviceInfo;
            }
        }
        $verificationFiles = [];
        foreach (self::DOCUMENT_FIELDS as $field) {
            $file = $this->request->getFile('verification_documents.' . $field);
            $captureError = $this->validateLiveCapture($file, $field, $metadata[$field] ?? null);
            if ($captureError !== null) {
                return redirect()->back()->withInput()->with('error', $captureError);
            }
            $verificationFiles[$field] = $file;
        }

        // Only Government ID and proof of address need readable text. A selfie
        // and home photo only need to be real, well-lit live captures. The ID
        // cannot be accepted unless OCR can confirm its text and name match.
        $ocrService = new DocumentOcrService(); $ocrResults = [];
        foreach (['gov_id', 'address_proof'] as $documentType) {
            $expectedIdType = $documentType === 'gov_id'
                ? (string) ($metadata['gov_id']['id_verification']['selected_type'] ?? '')
                : null;
            $ocrResults[$documentType] = $ocrService->screen($verificationFiles[$documentType]->getTempName(), $documentType, trim((string) $post['full_name']), $expectedIdType);
            if (! $ocrResults[$documentType]['passed']) return redirect()->back()->withInput()->with('error', $ocrResults[$documentType]['message']);
        }
        $ocrScreening = ['status' => 'Passed', 'message' => 'Valid government-issued ID type and applicant name were confirmed, proof-of-address text was readable, the live selfie passed its face challenge, and the home photo passed image-quality checks.'];

        $applicationCode = $this->newApplicationCode();
        $applicationModel = new AdoptionApplicationModel();
        $applicationId = $applicationModel->insert([
            'application_id' => $applicationCode,
            'adopter_id' => $adopterId,
            'cat_id' => $catId ?: null,
            'cat_description' => $cats ? implode(', ', array_map(static fn (array $selectedCat): string => $selectedCat['name'] . ' — ' . $selectedCat['breed'], $cats)) : trim((string) $post['cat_description']),
            'full_name' => trim((string) $post['full_name']),
            'address' => trim((string) $post['address']),
            'phone' => trim((string) $post['phone']),
            'occupation' => trim((string) $post['occupation']),
            'housing_type' => null,
            'compatibility_score' => 0,
            'assessment_status' => 'Processing',
            'indoor_living_compliance' => 'Pending Staff Review',
            'humane_housing_compliance' => 'Pending Staff Review',
            'existing_pet_concern' => 0,
            'caretaker_when_away' => trim((string) ($post['assessment_backup'] ?? '')),
            'verification_metadata' => json_encode($metadata, JSON_UNESCAPED_SLASHES),
            'ocr_status' => $ocrScreening['status'],
            'ocr_message' => $ocrScreening['message'],
            'status' => 'Under Review',
            'submitted_at' => date('Y-m-d H:i:s'),
        ], true);
        if (! $applicationId) {
            return redirect()->back()->withInput()->with('error', 'Your application could not be saved. Please try again.');
        }
        $assessmentService = new AdoptionAssessmentService();
        $assessmentService->createResponses($applicationId, $post);
        $groupAnswers = [];
        foreach ($assessmentService->groups() as $group => $definition) {
            $parts = ['Main answer: ' . trim((string) ($post['assessment_' . $group . '_main'] ?? ''))];
            foreach ($definition['subquestions'] ?? [] as $index => $_subquestion) $parts[] = 'Subquestion ' . ($index + 1) . ': ' . trim((string) ($post['assessment_' . $group . '_sub_' . ($index + 1)] ?? ''));
            $groupAnswers[$group] = implode("\n\n", $parts);
        }
        foreach ($assessmentService->flags($groupAnswers) as [$group, $status, $reason, $criterion]) {
            (new AdoptionAssessmentFlagModel())->insert(['application_id' => $applicationId, 'question_group' => $group, 'status' => $status, 'reason' => $reason, 'criterion_key' => $criterion, 'related_answers' => $groupAnswers[$group] ?? null]);
        }
        foreach ($assessmentService->consistencyFlags($post) as [$group, $status, $reason, $criterion, $relatedAnswers]) {
            (new AdoptionAssessmentFlagModel())->insert(['application_id' => $applicationId, 'question_group' => $group, 'status' => $status, 'reason' => $reason, 'criterion_key' => $criterion, 'related_answers' => $relatedAnswers]);
        }
        $assessment = $assessmentService->calculate((new \App\Models\AdoptionAssessmentModel())->where('application_id', $applicationId)->findAll());
        $applicationModel->update($applicationId, [
            'assessment_score' => $assessment['score'],
            'assessment_status' => $assessmentService->category($assessment['score'], $assessment['complete']),
            'compatibility_score' => (int) round((float) ($assessment['score'] ?? 0)),
            'assessment_updated_at' => date('Y-m-d H:i:s'),
        ]);

        $storage = new CloudStorageService();
        $paths = [];
        try {
            foreach (self::DOCUMENT_FIELDS as $field) {
                $file = $verificationFiles[$field];
                $filename = strtolower($field . '_' . $applicationCode . '_' . bin2hex(random_bytes(6)) . '.jpg');
                $paths[$field . '_path'] = $storage->store($file, 'applications', $filename);
            }
            $applicationModel->update($applicationId, $paths);
            foreach ($cats as $selectedCat) {
                (new ApplicationCatModel())->insert(['application_id' => $applicationId, 'cat_id' => $selectedCat['id'], 'status' => 'Under Review']);
            }
            // Keep the capture request focused on saving the application and
            // photos. Related profile and per-cat records are updated during
            // staff review, so they cannot delay the applicant's redirect.
        } catch (\Throwable $exception) {
            foreach ($paths as $path) {
                try {
                    $storage->delete($path);
                } catch (\Throwable) {
                }
            }
            $applicationModel->delete($applicationId);
            log_message('error', 'Application document upload failed: {message}', ['message' => $exception->getMessage()]);
            return redirect()->back()->withInput()->with('error', 'The documents could not be saved. Please try again.');
        }

        session()->setFlashdata('app_submitted', true);
        session()->setFlashdata('app_code', $applicationCode);
        return redirect()->to('/adopter/apply/success');
    }

    public function success()
    {
        if (! session('app_submitted')) {
            return redirect()->to('/adopter/application');
        }
        return view('adopter/application_success', ['title' => 'Application Submitted', 'appCode' => session('app_code')]);
    }

    public function myApplications()
    {
        $adopterId = (int) session('adopter_user.adopter_id');
        $applications = (new AdoptionApplicationModel())
            ->select('adoption_applications.*, animals.name AS cat_name, animals.breed, animals.age, animals.image_url')
            ->join('animals', 'animals.id = adoption_applications.cat_id', 'left')
            ->where('adoption_applications.adopter_id', $adopterId)
            ->orderBy('submitted_at', 'DESC')
            ->findAll();
        return view('adopter/my_applications', ['title' => 'My Applications', 'applications' => $applications]);
    }

    private function newApplicationCode(): string
    {
        do {
            $code = 'APP-' . strtoupper(bin2hex(random_bytes(3)));
        } while ((new AdoptionApplicationModel())->where('application_id', $code)->first());
        return $code;
    }

    private function validateLiveCapture($file, string $field, $metadata): ?string
    {
        $label = ucwords(str_replace('_', ' ', $field));
        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return "Capture a valid {$label} photo using the in-app camera.";
        }
        if (! is_array($metadata) || ($metadata['capture_source'] ?? '') !== 'in_app_camera') {
            return "{$label} must be captured with the in-app camera.";
        }
        $capturedAt = strtotime((string) ($metadata['captured_at'] ?? ''));
        if ($capturedAt === false || abs(time() - $capturedAt) > 15 * 60) {
            return "{$label} must be captured live within the last 15 minutes.";
        }
        if ($field === 'selfie') {
            $livenessError = $this->validateFaceLivenessMetadata($metadata, $capturedAt);
            if ($livenessError !== null) return $livenessError;
        }
        if ($field === 'gov_id') {
            $idError = $this->validateIdVerificationMetadata($metadata, $capturedAt);
            if ($idError !== null) return $idError;
        }
        $latitude = filter_var($metadata['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($metadata['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
        $accuracy = filter_var($metadata['accuracy'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($latitude === false || $longitude === false || $accuracy === false || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180 || $accuracy < 0) {
            return "{$label} needs a valid current GPS location.";
        }
        if ($file->getSize() < 8 * 1024 || $file->getSizeByUnit('mb') > 10 || ! in_array(strtolower($file->getExtension()), ['jpg', 'jpeg'], true)) {
            return "{$label} must be a live JPEG photo between 8 KB and 10 MB.";
        }
        $imageInfo = @getimagesize($file->getTempName());
        if (! $imageInfo || $imageInfo['mime'] !== 'image/jpeg' || $imageInfo[0] < 320 || $imageInfo[1] < 240) {
            return "{$label} must be a clear live JPEG photo at least 320 × 240 pixels.";
        }
        if ($this->isNearlyBlankImage($file->getTempName())) {
            return "{$label} appears blank, plain, or too dark. Please retake a clear photo of the required document or area.";
        }
        return null;
    }

    private function validateFaceLivenessMetadata(array $metadata, int $capturedAt): ?string
    {
        $liveness = $metadata['face_liveness'] ?? null;
        $verifiedAt = is_array($liveness) ? strtotime((string) ($liveness['verified_at'] ?? '')) : false;
        $challenge = is_array($liveness) ? (string) ($liveness['challenge'] ?? '') : '';
        $provider = is_array($liveness) ? (string) ($liveness['provider'] ?? '') : '';
        $secondsFromVerification = $verifiedAt === false ? -1 : $capturedAt - $verifiedAt;
        if (! is_array($liveness)
            || ($liveness['verified'] ?? false) !== true
            || ! in_array($challenge, ['blink', 'turn'], true)
            || $provider !== 'mediapipe_face_landmarker'
            || $secondsFromVerification < 0
            || $secondsFromVerification > 60) {
            return 'Complete the live blink or head-turn face challenge before capturing your selfie.';
        }

        return null;
    }

    private function validateIdVerificationMetadata(array $metadata, int $capturedAt): ?string
    {
        $verification = $metadata['id_verification'] ?? null;
        $verifiedAt = is_array($verification) ? strtotime((string) ($verification['verified_at'] ?? '')) : false;
        $selectedType = is_array($verification) ? (string) ($verification['selected_type'] ?? '') : '';
        $detectedType = is_array($verification) ? (string) ($verification['detected_type'] ?? '') : '';
        $typeBasis = is_array($verification) ? (string) ($verification['type_basis'] ?? '') : '';
        $provider = is_array($verification) ? (string) ($verification['provider'] ?? '') : '';
        $confidence = is_array($verification) ? filter_var($verification['confidence'] ?? null, FILTER_VALIDATE_FLOAT) : false;
        $secondsFromVerification = $verifiedAt === false ? -1 : $capturedAt - $verifiedAt;
        $allowedTypes = ['philhealth', 'national_id', 'drivers_license', 'passport'];
        $validTypeEvidence = $detectedType === $selectedType && $typeBasis === 'ocr_marker';

        if (! is_array($verification)
            || ($verification['verified'] ?? false) !== true
            || ($verification['name_match'] ?? false) !== true
            || ($verification['document_detected'] ?? false) !== true
            || ! in_array($selectedType, $allowedTypes, true)
            || ! $validTypeEvidence
            || $provider !== 'tesseract_js'
            || $confidence === false
            || $confidence < 8
            || $secondsFromVerification < 0
            || $secondsFromVerification > 90) {
            return 'Scan and pass the selected valid government-issued ID type and Full Name check before capturing it.';
        }

        return null;
    }

    /** Reject black/lens-covered and near-solid frames without counting the GPS stamp. */
    private function isNearlyBlankImage(string $path): bool
    {
        if (! function_exists('imagecreatefromjpeg')) return true;
        $image = @imagecreatefromjpeg($path);
        if (! $image) return true;

        $width = imagesx($image);
        $height = imagesy($image);
        $samples = 0; $sum = 0.0; $sumSquares = 0.0; $darkSamples = 0;
        // Ignore the lower GPS/time overlay: it must not validate a plain frame.
        for ($y = (int) ($height * 0.10); $y < (int) ($height * 0.68); $y += max(1, (int) ($height / 12))) {
            for ($x = (int) ($width * 0.10); $x < (int) ($width * 0.90); $x += max(1, (int) ($width / 12))) {
                $rgb = imagecolorat($image, $x, $y);
                $brightness = ((($rgb >> 16) & 0xFF) * 0.299) + ((($rgb >> 8) & 0xFF) * 0.587) + (($rgb & 0xFF) * 0.114);
                $sum += $brightness; $sumSquares += $brightness * $brightness; ++$samples;
                if ($brightness < 45) ++$darkSamples;
            }
        }
        imagedestroy($image);
        $mean = $sum / max(1, $samples);
        $variance = ($sumSquares / max(1, $samples)) - ($mean ** 2);
        // Reject lens-covered/very dim frames even if a small bright GPS stamp
        // or light reflection raises the average slightly.
        return $mean < 45 || ($darkSamples / max(1, $samples)) > 0.82 || $variance < 25;
    }
}

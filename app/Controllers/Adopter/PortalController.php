<?php

namespace App\Controllers\Adopter;

use App\Controllers\BaseController;
use App\Models\AdopterModel;
use App\Models\AdoptionApplicationModel;
use App\Models\AnimalModel;
use App\Models\PostAdoptionUpdateModel;
use App\Models\MonitoringSubmissionModel;
use App\Services\CloudStorageService;
use App\Models\ApplicationCatModel;
use App\Models\AnimalPhotoModel;
use App\Models\AdoptionAssessmentModel;
use App\Services\AdoptionAssessmentService;

class PortalController extends BaseController
{
    public function index(int $adoptionId = null)
    {
        helper('monitoring');
        $user = session('adopter_user');
        if (! $user) {
            return redirect()->to('/')->with('error', 'Please sign in to continue.')->with('auth_modal', 'signin')->with('signin_type', 'adopter');
        }

        $adopter = (new AdopterModel())->find($user['adopter_id']);
        $adoptedCats = (new AnimalModel())
            ->join('adoptions', 'adoptions.animal_id = animals.id')
            ->where('adoptions.adopter_id', $user['adopter_id'])
            ->where('adoptions.status', 'Active')
            ->select('animals.*, adoptions.id AS adoption_id, adoptions.adoption_date')
            ->orderBy('adoptions.adoption_date', 'DESC')
            ->findAll();

        $updateModel = new MonitoringSubmissionModel();
        if ($adoptionId === null) {
            foreach ($adoptedCats as &$adoptedCat) {
                $adoptedCat['updates_completed'] = $updateModel->where('adoption_id', $adoptedCat['adoption_id'])->countAllResults();
                $adoptedCat['next_check_in'] = null;
                $completed = array_column($updateModel->where('adoption_id', $adoptedCat['adoption_id'])->findAll(), 'checkpoint');
                foreach (['3-day', '3-week', '3-month'] as $checkpoint) {
                    if (! in_array($checkpoint, $completed, true)) {
                        $dates = monitoringCheckpoints($adoptedCat['adoption_date']);
                        $adoptedCat['next_check_in'] = $dates[$checkpoint] ?? null;
                        break;
                    }
                }
            }
            unset($adoptedCat);

            return view('adopter/adoptions', [
                'title' => 'My Adoptions',
                'adopter' => $adopter,
                'adoptedCats' => $adoptedCats,
            ]);
        }

        $adoptedCat = null;
        foreach ($adoptedCats as $candidate) {
            if ((int) $candidate['adoption_id'] === $adoptionId) {
                $adoptedCat = $candidate;
                break;
            }
        }
        if (! $adoptedCat) {
            return redirect()->to('/adopter/portal')->with('error', 'Adoption record not found.');
        }

        // This page is for one adopted cat. Never show another cat's
        // monitoring submissions just because the adopter is the same.
        $totalUpdates = $updateModel->where('adoption_id', $adoptedCat['adoption_id'])->countAllResults();
        $pendingCount = $updateModel->where('adoption_id', $adoptedCat['adoption_id'])->where('status', 'Pending')->countAllResults();
        $recentUpdates = $updateModel
            ->where('adoption_id', $adoptedCat['adoption_id'])
            ->orderBy('submission_date', 'DESC')
            ->findAll(5);

        $checkpoints = $adoptedCat ? monitoringCheckpoints($adoptedCat['adoption_date']) : [];
        $completedCheckpoints = $adoptedCat ? array_column($updateModel->where('adoption_id', $adoptedCat['adoption_id'])->findAll(), 'checkpoint') : [];
        $nextCheckpoint = null;
        foreach (['3-day', '3-week', '3-month'] as $checkpoint) { if (! in_array($checkpoint, $completedCheckpoints, true)) { $nextCheckpoint = $checkpoint; break; } }
        $nextCheckIn = $nextCheckpoint ? ($checkpoints[$nextCheckpoint] ?? null) : null;

        // Resolve the application that belongs to this adopted cat. Newer
        // multi-cat applications use adoption_application_cats, while older
        // records keep the selected cat directly on adoption_applications.
        $applicationModel = new AdoptionApplicationModel();
        $applicationLink = (new ApplicationCatModel())
            ->select('adoption_application_cats.application_id')
            ->join('adoption_applications', 'adoption_applications.id = adoption_application_cats.application_id')
            ->where('adoption_applications.adopter_id', $user['adopter_id'])
            ->where('adoption_application_cats.cat_id', $adoptedCat['id'])
            ->orderBy('adoption_applications.submitted_at', 'DESC')
            ->first();
        $portalApplication = $applicationLink
            ? $applicationModel->find((int) $applicationLink['application_id'])
            : $applicationModel
                ->where('adopter_id', $user['adopter_id'])
                ->where('cat_id', $adoptedCat['id'])
                ->orderBy('submitted_at', 'DESC')
                ->first();

        if ($portalApplication) {
            $assessmentRows = (new AdoptionAssessmentModel())
                ->where('application_id', $portalApplication['id'])
                ->findAll();
            $rowsByKey = array_column($assessmentRows, null, 'criterion_key');
            $portalApplication['assessment_rows'] = [];
            foreach ((new AdoptionAssessmentService())->criteria() as $key => $criterion) {
                $portalApplication['assessment_rows'][] = array_merge(
                    $criterion,
                    $rowsByKey[$key] ?? ['criterion_key' => $key, 'response' => '', 'rating' => null, 'earned_points' => null],
                );
            }
            if (isset($rowsByKey['overall_reflection'])) {
                $portalApplication['assessment_rows'][] = array_merge([
                    'criterion_key' => 'overall_reflection',
                    'label' => 'Overall Readiness and Responsible Follow-Through',
                    'weight' => 0,
                ], $rowsByKey['overall_reflection']);
            }
        }

        return view('adopter/portal', [
            'title' => 'Adopter Portal',
            'adopter' => $adopter,
            'cat' => $adoptedCat,
            'totalUpdates' => $totalUpdates,
            'pendingCount' => $pendingCount,
            'nextCheckIn' => $nextCheckIn,
            'recentUpdates' => $recentUpdates,
            'checkpoints' => $checkpoints,
            'completedCheckpoints' => $completedCheckpoints,
            'nextCheckpoint' => $nextCheckpoint,
            'portalApplication' => $portalApplication,
            'galleryPhotos' => (new AnimalPhotoModel())
                ->where('animal_id', $adoptedCat['id'])
                ->orderBy('created_at', 'DESC')
                ->findAll(),
        ]);
    }

    public function home()
    {
        $user = session('adopter_user');
        if (! $user) {
            return redirect()->to('/')->with('error', 'Please sign in to continue.')->with('auth_modal', 'signin')->with('signin_type', 'adopter');
        }

        $adopter = (new AdopterModel())->find($user['adopter_id']);
        $availableCats = (new AnimalModel())
            ->where('status', 'Available')
            ->orderBy('name', 'ASC')
            ->findAll();

        $adopterName = isset($adopter['name']) ? $adopter['name'] : trim((isset($adopter['first_name']) ? $adopter['first_name'] : '') . ' ' . (isset($adopter['last_name']) ? $adopter['last_name'] : ''));

        return view('adopter/home', [
            'title' => 'Meet the Cats',
            'adopterName' => $adopterName,
            'availableCats' => $availableCats,
            'cats' => $availableCats,
            'catCount' => count($availableCats),
            'applicationCount' => (new AdoptionApplicationModel())->where('adopter_id', $user['adopter_id'])->countAllResults(),
            'compliance' => round($adopter['compliance_score'] ?? 91),
        ]);
    }

    public function catProfile(int $catId)
    {
        $cat = (new AnimalModel())->where('id', $catId)->where('status', 'Available')->first();
        if (! $cat) return redirect()->to('/adopter/home')->with('error', 'This cat is no longer available for adoption.');
        return view('adopter/cat_profile', [
            'title' => $cat['name'] . ' · Cat Profile',
            'cat' => $cat,
            'galleryPhotos' => (new AnimalPhotoModel())->where('animal_id', $catId)->orderBy('created_at', 'DESC')->findAll(),
        ]);
    }

    public function applyForm(int $catId = null)
    {
        $user = session('adopter_user');
        $adopter = (new AdopterModel())->find($user['adopter_id']);
        $animal = null;
        $availableCats = (new AnimalModel())
            ->where('status', 'Available')
            ->orderBy('name', 'ASC')
            ->findAll();

        if ($catId) {
            $animal = (new AnimalModel())->find($catId);
            if (! $animal || $animal['status'] !== 'Available') {
                return redirect()->to('/adopter/home')->with('error', 'That cat is no longer available for adoption.');
            }
        }

        return view('adopter/apply', [
            'title' => 'Adoption Application',
            'animal' => $animal,
            'availableCats' => $availableCats,
            'adopter' => $adopter,
        ]);
    }

    public function applySubmit()
    {
        $user = session('adopter_user');
        if (! $user) {
            return redirect()->to('/')->with('error', 'Please sign in to submit an application.')->with('auth_modal', 'signin');
        }

        $post = $this->request->getPost();
        (new AdopterModel())->update($user['adopter_id'], [
            'phone' => trim((string) ($post['phone'] ?? '')),
            'address' => trim((string) ($post['address'] ?? '')),
        ]);

        $required = ['full_name', 'phone', 'address', 'occupation', 'pet_ownership_experience', 'monthly_pet_budget', 'housing_type', 'caretaker_when_away'];
        foreach ($required as $field) {
            if (! trim((string) $post[$field])) {
                return redirect()->back()->withInput()->with('error', 'Please complete all required fields.');
            }
        }
        $monthlyBudget = filter_var($post['monthly_pet_budget'], FILTER_VALIDATE_FLOAT);
        if ($monthlyBudget === false || $monthlyBudget < 0) {
            return redirect()->back()->withInput()->with('error', 'Enter a valid estimated monthly pet-care budget.');
        }

        $commitmentFields = ['lifetime_commitment', 'secured_space', 'food_water', 'vet_bills', 'spay_neuter', 'deworming', 'tick_flea', 'family_comfortable', 'love_attention'];
        foreach ($commitmentFields as $field) {
            if (! in_array($post[$field] ?? '', ['yes', 'no'], true)) {
                return redirect()->back()->withInput()->with('error', 'Please answer every commitment and readiness question.');
            }
        }

        $catIds = array_values(array_unique(array_filter(array_map('intval', (array) ($post['cat_ids'] ?? [])))));
        if (empty($catIds)) {
            return redirect()->back()->withInput()->with('error', 'Please add at least one cat to your application.');
        }
        $cats = (new AnimalModel())->whereIn('id', $catIds)->where('status', 'Available')->findAll();
        if (count($cats) !== count($catIds)) {
            return redirect()->to('/adopter/home')->with('error', 'One or more selected cats are no longer available for adoption.');
        }
        $cat = $cats[0];

        $verificationMetadata = json_decode((string) ($post['verification_metadata'] ?? ''), true);
        if (! is_array($verificationMetadata)) {
            return redirect()->back()->withInput()->with('error', 'Capture all four live verification photos before submitting.');
        }
        $verificationFiles = [];
        foreach (['selfie', 'gov_id', 'address_proof', 'home_photos'] as $documentType) {
            $file = $this->request->getFile('verification_documents.' . $documentType);
            $error = $this->validateVerificationCapture($file, $documentType, $verificationMetadata[$documentType] ?? null);
            if ($error !== null) {
                return redirect()->back()->withInput()->with('error', $error);
            }
            $verificationFiles[$documentType] = $file;
        }

        $yesAnswers = count(array_filter($commitmentFields, static fn (string $field): bool => ($post[$field] ?? '') === 'yes'));
        $financialReadiness = $monthlyBudget >= 1500 ? 1 : 0;
        $experienceReadiness = in_array($post['pet_ownership_experience'], ['First-time adopter', 'Previous pet owner', 'Current pet owner'], true) ? 1 : 0;
        $compatibilityScore = (int) round((($yesAnswers + $financialReadiness + $experienceReadiness) / (count($commitmentFields) + 2)) * 100);
        $initialStatus = $compatibilityScore <= 60 ? 'Rejected' : 'Under Review';

        $applicationId = 'APP' . time() . rand(100, 999);
        $applicationModel = new AdoptionApplicationModel();
        $catDescription = trim($cat['name'] . ' — ' . $cat['breed']);

        if (! $applicationModel->insert([
            'application_id' => $applicationId,
            'adopter_id' => $user['adopter_id'],
            'cat_id' => $cat['id'],
            'cat_description' => implode(', ', array_map(static fn (array $selectedCat): string => $selectedCat['name'] . ' — ' . $selectedCat['breed'], $cats)),
            'full_name' => trim((string) $post['full_name']),
            'address' => trim((string) $post['address']),
            'phone' => trim((string) $post['phone']),
            'occupation' => trim((string) $post['occupation']),
            'pet_ownership_experience' => trim((string) $post['pet_ownership_experience']),
            'monthly_pet_budget' => $monthlyBudget,
            'housing_type' => trim((string) $post['housing_type']),
            'compatibility_score' => $compatibilityScore,
            'lifetime_commitment' => $post['lifetime_commitment'] === 'yes' ? 1 : 0,
            'secured_space' => $post['secured_space'] === 'yes' ? 1 : 0,
            'food_water' => $post['food_water'] === 'yes' ? 1 : 0,
            'vet_bills' => $post['vet_bills'] === 'yes' ? 1 : 0,
            'spay_neuter' => $post['spay_neuter'] === 'yes' ? 1 : 0,
            'deworming' => $post['deworming'] === 'yes' ? 1 : 0,
            'tick_flea' => $post['tick_flea'] === 'yes' ? 1 : 0,
            'family_comfortable' => $post['family_comfortable'] === 'yes' ? 1 : 0,
            'love_attention' => $post['love_attention'] === 'yes' ? 1 : 0,
            'caretaker_when_away' => trim((string) $post['caretaker_when_away']),
            'selfie_path' => null,
            'gov_id_path' => null,
            'address_proof_path' => null,
            'home_photos_path' => null,
            'verification_metadata' => null,
            'ocr_status' => null,
            'ocr_message' => null,
            'status' => $initialStatus,
            'submitted_at' => date('Y-m-d H:i:s'),
        ])) {
            log_message('error', 'Adoption application insert failed: {errors}', ['errors' => json_encode($applicationModel->errors())]);
            return redirect()->back()->withInput()->with('error', 'Your application could not be saved. Please try again.');
        }

        $applicationDbId = (int) $applicationModel->getInsertID();
        $storage = new CloudStorageService();
        $documentPaths = [];
        try {
            foreach ($verificationFiles as $documentType => $file) {
                $filename = strtolower($documentType . '_' . $applicationId . '_' . bin2hex(random_bytes(8)) . '.jpg');
                $documentPaths[$documentType] = $storage->store($file, 'applications', $filename);
            }
        } catch (\Throwable $exception) {
            foreach ($documentPaths as $path) {
                try {
                    $storage->delete($path);
                } catch (\Throwable) {
                }
            }
            $applicationModel->delete($applicationDbId);
            log_message('error', 'Live application document save failed: {message}', ['message' => $exception->getMessage()]);
            return redirect()->back()->withInput()->with('error', 'The verification photos could not be saved. Please capture them again.');
        }

        $applicationModel->update($applicationDbId, [
            'selfie_path' => $documentPaths['selfie'],
            'gov_id_path' => $documentPaths['gov_id'],
            'address_proof_path' => $documentPaths['address_proof'],
            'home_photos_path' => $documentPaths['home_photos'],
            'verification_metadata' => json_encode($verificationMetadata, JSON_UNESCAPED_SLASHES),
            'ocr_status' => null,
            'ocr_message' => null,
            'status' => $initialStatus,
        ]);
        $applicationCatModel = new ApplicationCatModel();
        foreach ($cats as $selectedCat) {
            $applicationCatModel->insert(['application_id' => $applicationDbId, 'cat_id' => $selectedCat['id'], 'status' => $initialStatus]);
        }

        // Do not send email while handling the applicant's upload request. SMTP
        // connections can block for a long time (or time out), leaving the
        // browser on a perpetual loading state even though the application has
        // already been saved. Staff can send the decision email after review.
        if ($initialStatus === 'Rejected') {
            log_message('notice', 'Application {applicationId} was flagged for staff review before a decision email is sent.', [
                'applicationId' => $applicationId,
            ]);
        }

        return redirect()->to('/adopter/apply/success')->with('success', 'Application submitted successfully.');
    }

    private function sendAutomaticRejectionEmail(string $recipient, string $name, string $catName, string $reason): void
    {
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeCat = htmlspecialchars($catName, ENT_QUOTES, 'UTF-8');
        $email = service('email');
        $config = config('Email');
        $email->setFrom($config->fromEmail ?: 'no-reply@catcare360.org', $config->fromName ?: 'CatCare 360');
        $email->setTo($recipient);
        $email->setSubject('Update on your adoption application');
        $email->setMailType('html');
        $safeReason = htmlspecialchars($reason, ENT_QUOTES, 'UTF-8');
        $email->setMessage("<!doctype html><html><body style=\"margin:0;background:#f4f7fb;font-family:Arial,sans-serif;color:#172033;padding:28px 16px\"><div style=\"max-width:560px;margin:auto;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 8px 28px rgba(29,78,216,.1)\"><div style=\"background:#2563eb;color:#fff;padding:24px 30px;font-weight:bold;font-size:18px\">🐾 CatCare 360</div><div style=\"padding:30px\"><p style=\"margin:0 0 10px;color:#6b7280;font-size:14px\">Application decision</p><h1 style=\"margin:0 0 14px;font-size:24px;color:#dc2626\">Update on your adoption application</h1><p style=\"line-height:1.6;color:#4b5563\">Hello {$safeName},</p><p style=\"line-height:1.6;color:#4b5563\">Thank you for applying to adopt {$safeCat}. {$safeReason}</p><a href=\"" . site_url('adopter/application') . "\" style=\"display:inline-block;margin-top:12px;background:#2563eb;color:#fff;text-decoration:none;padding:12px 18px;border-radius:8px;font-weight:bold;font-size:14px\">View My Application</a></div><div style=\"padding:16px 30px;background:#f8fafc;color:#9ca3af;font-size:12px\">© 2026 CatCare 360</div></div></body></html>");
        if (! $email->send()) {
            log_message('error', 'Automatic application rejection email could not be sent to {email}.', ['email' => $recipient]);
        }
    }

    private function validateVerificationCapture($file, string $documentType, $metadata): ?string
    {
        $label = ucwords(str_replace('_', ' ', $documentType));
        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return "Please capture a valid {$label} photo.";
        }
        if (! is_array($metadata) || ($metadata['capture_source'] ?? '') !== 'in_app_camera') {
            return "{$label} must be captured with the in-app camera. Gallery uploads are not accepted.";
        }
        $capturedAt = strtotime((string) ($metadata['captured_at'] ?? ''));
        if ($capturedAt === false || abs(time() - $capturedAt) > 15 * 60) {
            return "{$label} must be captured live within the last 15 minutes. Please take a new photo.";
        }
        $latitude = filter_var($metadata['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($metadata['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
        $accuracy = filter_var($metadata['accuracy'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($latitude === false || $longitude === false || $accuracy === false || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180 || $accuracy < 0) {
            return "{$label} needs a valid current GPS location. Allow location access and capture it again.";
        }
        if ($file->getSize() < 8 * 1024 || $file->getSizeByUnit('mb') > 10) {
            return "{$label} must be between 8 KB and 10 MB.";
        }
        $extension = strtolower($file->getExtension());
        if (! in_array($extension, ['jpg', 'jpeg'], true)) {
            return "{$label} must be a live JPEG camera photo.";
        }

        $imageInfo = @getimagesize($file->getTempName());
        if (! $imageInfo || $imageInfo['mime'] !== 'image/jpeg' || $imageInfo[0] < 320 || $imageInfo[1] < 240) {
            return "{$label} must be a clear live JPEG photo at least 320 × 240 pixels.";
        }
        return null;
    }

    private function isNearlyBlankImage(string $path): bool
    {
        $image = @imagecreatefromstring((string) @file_get_contents($path));
        if (! $image) {
            return true;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $samples = 0;
        $sum = 0;
        $sumSquares = 0;
        // Examine only the upper 68% of the frame. The bottom portion has the
        // system GPS/time stamp, which must not make a black or blank capture pass.
        for ($y = (int) ($height * 0.1); $y < (int) ($height * 0.68); $y += max(1, (int) ($height / 12))) {
            for ($x = (int) ($width * 0.1); $x < (int) ($width * 0.9); $x += max(1, (int) ($width / 12))) {
                $rgb = imagecolorat($image, $x, $y);
                $brightness = (((($rgb >> 16) & 0xFF) * 0.299) + ((($rgb >> 8) & 0xFF) * 0.587) + (($rgb & 0xFF) * 0.114));
                $sum += $brightness;
                $sumSquares += $brightness * $brightness;
                ++$samples;
            }
        }
        imagedestroy($image);
        $mean = $sum / max(1, $samples);
        $variance = ($sumSquares / max(1, $samples)) - ($mean ** 2);

        // Reject a lens-covered/black screen immediately. Plain white or black
        // frames with no real content are also rejected before OCR is called.
        return $mean < 18 || $variance < 25;
    }

    public function applySuccess()
    {
        return view('adopter/apply_success', ['title' => 'Application Submitted']);
    }

    public function application()
    {
        $user = session('adopter_user');
        if (! $user) {
            return redirect()->to('/')->with('error', 'Please sign in to continue.')->with('auth_modal', 'signin')->with('signin_type', 'adopter');
        }

        $applications = (new AdoptionApplicationModel())
            ->select('adoption_applications.*, animals.name AS cat_name, animals.breed AS cat_breed, animals.age AS cat_age, animals.image_url AS cat_photo')
            ->join('animals', 'animals.id = adoption_applications.cat_id', 'left')
            ->where('adopter_id', $user['adopter_id'])
            ->orderBy('submitted_at', 'DESC')
            ->findAll();
        $applicationCatModel = new ApplicationCatModel();
        $appliedCats = [];
        foreach ($applications as &$application) {
            $application['requested_cats'] = $applicationCatModel->select('adoption_application_cats.*, animals.name, animals.breed, animals.age, animals.image_url')->join('animals', 'animals.id = adoption_application_cats.cat_id')->where('application_id', $application['id'])->findAll();

            // An application can contain more than one cat. The adopter-facing
            // list is intentionally flat so every cat has its own status card.
            foreach ($application['requested_cats'] as $requestedCat) {
                $appliedCats[] = [
                    'application' => $application,
                    'cat' => $requestedCat,
                ];
            }

            // Keep older single-cat applications visible as well.
            if (empty($application['requested_cats']) && ! empty($application['cat_id'])) {
                $appliedCats[] = [
                    'application' => $application,
                    'cat' => [
                        'id' => null,
                        'cat_id' => $application['cat_id'],
                        'name' => $application['cat_name'] ?? $application['cat_description'] ?? 'Selected cat',
                        'breed' => $application['cat_breed'] ?? null,
                        'age' => $application['cat_age'] ?? null,
                        'image_url' => $application['cat_photo'] ?? null,
                        'status' => $application['status'] ?? 'Under Review',
                    ],
                ];
            }
        }
        unset($application);

        return view('adopter/application', [
            'title' => 'My Applications',
            'applications' => $applications,
            'appliedCats' => $appliedCats,
        ]);
    }

    public function updateProfile()
    {
        $user = session('adopter_user');
        if (! $user) return redirect()->to('/')->with('error', 'Please sign in to continue.');
        $phone = trim((string) $this->request->getPost('phone'));
        $address = trim((string) $this->request->getPost('address'));
        if ($phone === '' || $address === '') return redirect()->back()->with('error', 'Phone number and complete address are required.');
        (new AdopterModel())->update($user['adopter_id'], ['phone' => $phone, 'address' => $address]);
        $user['phone'] = $phone;
        $user['address'] = $address;
        session()->set('adopter_user', $user);
        session()->remove('adopter_profile_setup_required');
        return redirect()->back()->with('success', 'Profile contact details updated.');
    }

    public function applicationProgress(int $applicationId, int $applicationCatId = null)
    {
        $user = session('adopter_user');
        if (! $user) {
            return redirect()->to('/')->with('error', 'Please sign in to continue.')->with('auth_modal', 'signin')->with('signin_type', 'adopter');
        }

        $application = (new AdoptionApplicationModel())
            ->select('adoption_applications.*, animals.name AS cat_name, animals.breed AS cat_breed, animals.age AS cat_age, animals.image_url AS cat_photo')
            ->join('animals', 'animals.id = adoption_applications.cat_id', 'left')
            ->where('adoption_applications.id', $applicationId)
            ->where('adoption_applications.adopter_id', $user['adopter_id'])
            ->first();

        if (! $application) {
            return redirect()->to('/adopter/application')->with('error', 'Application not found.');
        }
        $application['requested_cats'] = (new ApplicationCatModel())->select('adoption_application_cats.*, animals.name, animals.breed, animals.age, animals.image_url')->join('animals', 'animals.id = adoption_application_cats.cat_id')->where('application_id', $applicationId)->findAll();
        $selectedCat = null;
        foreach ($application['requested_cats'] as $requestedCat) {
            if ($applicationCatId === null || (int) $requestedCat['id'] === $applicationCatId) {
                $selectedCat = $requestedCat;
                break;
            }
        }
        if (! $selectedCat && empty($application['requested_cats']) && $applicationCatId === null && ! empty($application['cat_id'])) {
            $selectedCat = [
                'id' => null,
                'cat_id' => $application['cat_id'],
                'name' => $application['cat_name'] ?? $application['cat_description'] ?? 'Selected cat',
                'breed' => $application['cat_breed'] ?? null,
                'age' => $application['cat_age'] ?? null,
                'image_url' => $application['cat_photo'] ?? null,
                'status' => $application['status'] ?? 'Under Review',
            ];
        }
        if (! $selectedCat) return redirect()->to('/adopter/application/' . $applicationId)->with('error', 'Requested cat not found.');

        $assessmentRows = (new AdoptionAssessmentModel())->where('application_id', $applicationId)->findAll();
        $rowsByKey = array_column($assessmentRows, null, 'criterion_key');
        $application['assessment_rows'] = [];
        foreach ((new AdoptionAssessmentService())->criteria() as $key => $criterion) {
            $application['assessment_rows'][] = array_merge($criterion, $rowsByKey[$key] ?? ['criterion_key' => $key, 'response' => '', 'rating' => null, 'earned_points' => null]);
        }
        $application['assessment_rows'][] = array_merge([
            'criterion_key' => 'overall_reflection',
            'label' => '6. Overall Readiness and Responsible Follow-Through',
            'weight' => 0,
            'response' => 'Not provided — this application was submitted before the reflection section was saved separately.',
            'rating' => null,
            'earned_points' => 0,
        ], $rowsByKey['overall_reflection'] ?? []);

        return view('adopter/application_progress', [
            'title' => 'Application Progress',
            'application' => $application,
            'selectedCat' => $selectedCat,
        ]);
    }

    public function submitUpdate()
    {
        $user = session('adopter_user');
        if (! $user) {
            return redirect()->to('/')->with('error', 'Please sign in to continue.')->with('auth_modal', 'signin')->with('signin_type', 'adopter');
        }
        $adopter = (new AdopterModel())->find($user['adopter_id']);
        if (empty(trim((string) ($adopter['address'] ?? '')))) {
            return redirect()->back()->withInput()->with('error', 'Add your complete address in your Adopter Profile before submitting a live update.');
        }

        $post = $this->request->getPost();
        $photo = $this->request->getFile('photo');

        if (! trim((string) ($post['update_type'] ?? '')) || ! trim((string) ($post['description'] ?? '')) || ! trim((string) ($post['cat_id'] ?? '')) || ! $photo?->isValid()) {
            return redirect()->back()->withInput()->with('error', 'Please complete the update form and capture a photo.');
        }

        if (($post['capture_source'] ?? '') !== 'in_app_camera') {
            return redirect()->back()->withInput()->with('error', 'For verification, photos must be taken with the in-app camera. Gallery uploads are not accepted.');
        }

        $latitude = filter_var($post['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($post['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
        $accuracy = filter_var($post['gps_accuracy'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($latitude === false || $longitude === false || $accuracy === false || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return redirect()->back()->withInput()->with('error', 'Location verification is required. Please allow location access, then capture again.');
        }

        $extension = strtolower((string) $photo->getExtension());
        $imageInfo = @getimagesize($photo->getTempName());
        if (! in_array($extension, ['jpg', 'jpeg'], true) || ! $imageInfo || $imageInfo['mime'] !== 'image/jpeg' || $imageInfo[0] < 320 || $imageInfo[1] < 240 || $photo->getSize() > 10 * 1024 * 1024) {
            return redirect()->back()->withInput()->with('error', 'The camera capture must be a valid JPEG photo (at least 320 × 240 and no more than 10 MB).');
        }

        $adoption = (new \App\Models\AdoptionModel())->where('adopter_id', $user['adopter_id'])->where('animal_id', $post['cat_id'])->where('status', 'Active')->first();
        if (! $adoption) return redirect()->back()->with('error', 'No active adoption was found for this cat.');
        $dates = monitoringCheckpoints($adoption['adoption_date']);
        $submissionModel = new MonitoringSubmissionModel();
        $checkpoint = null;
        foreach (['3-day', '3-week', '3-month'] as $candidate) {
            if (! $submissionModel->where('adoption_id', $adoption['id'])->where('checkpoint', $candidate)->first()) { $checkpoint = $candidate; break; }
        }
        if (! $checkpoint) return redirect()->back()->with('error', 'All required monitoring checkpoints have already been submitted.');

        $photoName = strtolower('update_' . time() . '_' . uniqid() . '.' . $photo->getExtension());
        try {
            $photoPath = (new CloudStorageService())->store($photo, 'updates', $photoName);
        } catch (\Throwable $exception) {
            log_message('error', 'Monitoring photo upload failed: {message}', ['message' => $exception->getMessage()]);
            return redirect()->back()->withInput()->with('error', 'Your monitoring photo could not be saved. Please try again.');
        }

        if (! $submissionModel->insert([
            'adopter_id' => $user['adopter_id'],
            'adoption_id' => $adoption['id'],
            'checkpoint' => $checkpoint,
            'photo_url' => $photoPath,
            'health_status' => $post['update_type'],
            'behavior_notes' => trim((string) $post['description']),
            'gps_lat' => $latitude,
            'gps_lng' => $longitude,
            'gps_accuracy' => $accuracy,
            'gps_verified' => true,
            'capture_source' => 'in_app_camera',
            'captured_at' => date('Y-m-d H:i:s'),
            'device_info' => substr(trim((string) ($post['device_info'] ?? '')), 0, 2000),
            'user_agent' => substr($this->request->getHeaderLine('User-Agent'), 0, 2000),
            'ip_address' => $this->request->getIPAddress(),
            'status' => 'Pending',
            'submission_date' => date('Y-m-d H:i:s'),
        ])) {
            log_message('error', 'Monitoring update insert failed: {errors}', ['errors' => json_encode($submissionModel->errors())]);
            return redirect()->back()->withInput()->with('error', 'Your update could not be saved. Please try again.');
        }

        recalculateAdopterMonitoringCompliance((int) $user['adopter_id']);

        return redirect()->to('/adopter/portal/' . $adoption['id'])->with('success', strtoupper($checkpoint) . ' update submitted successfully. It is now listed for staff review.');
    }
}

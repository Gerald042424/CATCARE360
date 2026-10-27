<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdopterModel;
use App\Models\AdoptionApplicationModel;
use App\Models\AdoptionModel;
use App\Models\AnimalModel;
use App\Models\ApplicationCatModel;
use App\Models\AnimalJourneyEventModel;
use App\Models\AdoptionAssessmentModel;
use App\Services\AdoptionAssessmentService;
use App\Models\AdoptionAssessmentFlagModel;

class ApplicationsController extends BaseController
{
    public function assessment(int $id)
    {
        $application = (new AdoptionApplicationModel())->find($id);
        if (! $application) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Application not found.');
        $service = new AdoptionAssessmentService();
        $rows = (new AdoptionAssessmentModel())->where('application_id', $id)->findAll(); $byKey = array_column($rows, null, 'criterion_key');
        // Backfill applications submitted before automatic scoring was enabled.
        if ($rows !== [] && count(array_filter($rows, static fn (array $row): bool => $row['rating'] !== null)) === 0) {
            $assessmentModel = new AdoptionAssessmentModel();
            foreach ($rows as $row) {
                $rating = $service->automaticRating($row['criterion_key'], (string) $row['response']);
                $assessmentModel->update($row['id'], ['rating' => $rating, 'rating_reason' => $service->automaticRatingReason($row['criterion_key'], (string) $row['response']), 'earned_points' => $service->earnedPoints($rating, (float) $row['weight'])]);
            }
            $rows = $assessmentModel->where('application_id', $id)->findAll(); $byKey = array_column($rows, null, 'criterion_key');
            $calculation = $service->calculate($rows);
            (new AdoptionApplicationModel())->update($id, ['assessment_score' => $calculation['score'], 'assessment_status' => $service->category($calculation['score'], $calculation['complete']), 'compatibility_score' => (int) round((float) ($calculation['score'] ?? 0)), 'assessment_updated_at' => date('Y-m-d H:i:s')]);
            $application = (new AdoptionApplicationModel())->find($id);
        }
        $application['assessment_rows'] = [];
        foreach ($service->criteria() as $key => $criterion) $application['assessment_rows'][] = array_merge($criterion, $byKey[$key] ?? ['criterion_key' => $key, 'response' => '', 'rating' => null, 'rating_reason' => '', 'earned_points' => null]);
        $application['assessment_flags'] = (new AdoptionAssessmentFlagModel())->where('application_id', $id)->orderBy('created_at', 'ASC')->findAll();
        return view('admin/applications/assessment', ['title' => 'Application Assessment', 'application' => $application]);
    }

    public function index(): string
    {
        $this->backfillAutomaticScores();
        $filters = [
            'search' => trim((string) $this->request->getGet('search')),
            'status' => trim((string) $this->request->getGet('status')),
            'score_band' => trim((string) $this->request->getGet('score_band')),
        ];
        $query = (new AdoptionApplicationModel())
            ->select('adoption_applications.*, animals.name AS animal_name, animals.image_url, adopters.email AS adopter_email')
            ->join('animals', 'animals.id = adoption_applications.cat_id', 'left')
            ->join('adopters', 'adopters.id = adoption_applications.adopter_id', 'left');
        if ($filters['search'] !== '') $query->groupStart()->like('adoption_applications.full_name', $filters['search'])->orLike('adoption_applications.application_id', $filters['search'])->orLike('animals.name', $filters['search'])->groupEnd();
        if ($filters['status'] !== '') $query->where('adoption_applications.status', $filters['status']);
        if ($filters['score_band'] === 'strong') $query->where('adoption_applications.assessment_score >=', 80);
        if ($filters['score_band'] === 'review') $query->where('adoption_applications.assessment_score >=', 60)->where('adoption_applications.assessment_score <', 80);
        if ($filters['score_band'] === 'low') $query->where('adoption_applications.assessment_score <', 60);
        $applications = $query->orderBy('submitted_at', 'DESC')->findAll();

        return view('admin/applications/index', ['title' => 'Applications', 'applications' => $applications, 'filters' => $filters]);
    }

    /** Scores records created before the automatic scorer was enabled. */
    private function backfillAutomaticScores(): void
    {
        $applicationModel = new AdoptionApplicationModel();
        $assessmentModel = new AdoptionAssessmentModel();
        $service = new AdoptionAssessmentService();
        foreach ($applicationModel->where('assessment_score', null)->findAll() as $application) {
            $rows = $assessmentModel->where('application_id', $application['id'])->findAll();
            if (count($rows) !== count($service->criteria())) continue;
            foreach ($rows as $row) {
                $rating = $service->automaticRating($row['criterion_key'], (string) $row['response']);
                $assessmentModel->update($row['id'], ['rating' => $rating, 'rating_reason' => $service->automaticRatingReason($row['criterion_key'], (string) $row['response']), 'earned_points' => $service->earnedPoints($rating, (float) $row['weight'])]);
            }
            $calculation = $service->calculate($assessmentModel->where('application_id', $application['id'])->findAll());
            $applicationModel->update($application['id'], ['assessment_score' => $calculation['score'], 'assessment_status' => $service->category($calculation['score'], $calculation['complete']), 'compatibility_score' => (int) round((float) ($calculation['score'] ?? 0)), 'assessment_updated_at' => date('Y-m-d H:i:s')]);
        }
    }

    public function detail(int $id)
    {
        $model = new AdoptionApplicationModel();
        $application = $model
            ->select('adoption_applications.*, animals.name AS animal_name, animals.image_url, animals.breed AS animal_breed')
            ->join('animals', 'animals.id = adoption_applications.cat_id', 'left')
            ->find($id);
        if (! $application) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Application not found.');
        }
        $application['requested_cats'] = (new ApplicationCatModel())
            ->select('adoption_application_cats.*, animals.name, animals.breed, animals.image_url')
            ->join('animals', 'animals.id = adoption_application_cats.cat_id')
            ->where('application_id', $id)->findAll();
        $assessmentService = new AdoptionAssessmentService();
        $assessmentRows = (new AdoptionAssessmentModel())->where('application_id', $id)->findAll();
        $rowsByKey = array_column($assessmentRows, null, 'criterion_key');
        $application['assessment_rows'] = [];
        foreach ($assessmentService->criteria() as $key => $criterion) {
            $application['assessment_rows'][] = array_merge($criterion, $rowsByKey[$key] ?? ['criterion_key' => $key, 'response' => '', 'rating' => null, 'rating_reason' => '', 'earned_points' => null]);
        }
        $application['assessment_rows'][] = array_merge(
            ['label' => '6. Overall Readiness and Responsible Follow-Through (unweighted)', 'weight' => 0],
            $rowsByKey['overall_reflection'] ?? ['criterion_key' => 'overall_reflection', 'response' => 'Not provided — this application was submitted before the reflection section was saved separately.', 'rating' => null, 'rating_reason' => 'Unweighted reflection and clarification check.', 'earned_points' => 0]
        );
        $application['assessment_flags'] = (new AdoptionAssessmentFlagModel())->where('application_id', $id)->orderBy('created_at', 'ASC')->findAll();

        if ($this->request->getMethod() === 'POST') {
            if ($this->request->getPost('action') === 'rescore_assessment') {
                $assessmentModel = new AdoptionAssessmentModel();
                foreach ($assessmentRows as $row) {
                    $rating = $assessmentService->automaticRating($row['criterion_key'], (string) $row['response']);
                    $assessmentModel->update($row['id'], ['rating' => $rating, 'rating_reason' => $assessmentService->automaticRatingReason($row['criterion_key'], (string) $row['response']), 'earned_points' => $assessmentService->earnedPoints($rating, (float) $row['weight'])]);
                }
                $calculation = $assessmentService->calculate($assessmentModel->where('application_id', $id)->findAll());
                $model->update($id, ['assessment_score' => $calculation['score'], 'assessment_status' => $assessmentService->category($calculation['score'], $calculation['complete']), 'compatibility_score' => (int) round((float) ($calculation['score'] ?? 0)), 'assessment_updated_at' => date('Y-m-d H:i:s')]);
                return redirect()->to('/admin/applications/' . $id . '/assessment')->with('success', 'System score recalculated from the submitted answers.');
            }
            if ($this->request->getPost('action') === 'save_assessment') {
                return redirect()->to('/admin/applications/' . $id . '/assessment')->with('error', 'Criterion scores are generated by the system. Use “Generate / refresh system score”.');
                $assessmentModel = new AdoptionAssessmentModel();
                $ratings = (array) $this->request->getPost('rating');
                $reasons = (array) $this->request->getPost('rating_reason');
                foreach ($assessmentService->criteria() as $key => $criterion) {
                    $rating = $ratings[$key] ?? null;
                    if (filter_var($rating, FILTER_VALIDATE_INT) === false || (int) $rating < 0 || (int) $rating > 100) return redirect()->back()->withInput()->with('error', 'Every criterion score must be a whole number from 0 to 100.');
                    $row = $rowsByKey[$key] ?? null;
                    if (! $row) return redirect()->back()->with('error', 'Assessment response record is missing.');
                    $assessmentModel->update($row['id'], ['rating' => (int) $rating, 'rating_reason' => trim((string) ($reasons[$key] ?? '')), 'earned_points' => $assessmentService->earnedPoints((int) $rating, (float) $criterion['weight'])]);
                }
                $updated = $assessmentModel->where('application_id', $id)->findAll();
                $calculation = $assessmentService->calculate($updated);
                $model->update($id, ['assessment_score' => $calculation['score'], 'assessment_status' => $assessmentService->category($calculation['score'], $calculation['complete']), 'compatibility_score' => (int) round((float) ($calculation['score'] ?? 0)), 'staff_notes' => trim((string) $this->request->getPost('staff_notes')), 'assessment_updated_at' => date('Y-m-d H:i:s')]);
                foreach ((array) $this->request->getPost('flag_notes') as $flagId => $note) (new AdoptionAssessmentFlagModel())->update((int) $flagId, ['review_notes' => trim((string) $note), 'review_status' => in_array(($this->request->getPost('flag_status')[(int) $flagId] ?? 'Open'), ['Open', 'Resolved'], true) ? $this->request->getPost('flag_status')[(int) $flagId] : 'Open', 'reviewed_by' => (int) session('staff_id'), 'reviewed_at' => date('Y-m-d H:i:s')]);
                return redirect()->to('/admin/applications/' . $id)->with('success', 'Assessment scores and review notes saved.');
            }
            $status = (string) $this->request->getPost('status');
            if (! in_array($status, ['Under Review', 'Approved', 'Rejected'], true)) {
                return redirect()->back()->with('error', 'Invalid application status.');
            }

            if ($status === 'Approved') {
                $adopter = (new AdopterModel())->find($application['adopter_id']);
                if ($adopter) {
                    (new AdopterModel())->update($application['adopter_id'], [
                        'name' => $application['full_name'], 'phone' => $application['phone'], 'address' => $application['address'],
                        'occupation' => $application['occupation'], 'housing_type' => $application['housing_type'],
                        // Suitability is an application score, not post-adoption
                        // monitoring. Monitoring begins only after submissions.
                        'next_check_in' => date('Y-m-d', strtotime('+3 days')),
                    ]);
                }
                $adoptionModel = new AdoptionModel();
                if (! $adoptionModel->where('adopter_id', $application['adopter_id'])->where('animal_id', $application['cat_id'])->first()) {
                    $adoptionModel->insert(['adopter_id' => $application['adopter_id'], 'animal_id' => $application['cat_id'], 'adoption_date' => date('Y-m-d'), 'status' => 'Active']);
                }
                (new AnimalModel())->update($application['cat_id'], ['status' => 'Adopted']);
                (new AnimalJourneyEventModel())->insert(['animal_id' => $application['cat_id'], 'event_type' => 'Adoption', 'title' => 'Adoption approved', 'description' => 'Adoption application approved and post-adoption monitoring started.', 'event_date' => date('Y-m-d')]);
                $this->closeCompetingApplications((int) $application['cat_id'], $id);
            }

            $model->update($id, ['status' => $status]);
            if ($status !== $application['status'] && in_array($status, ['Approved', 'Rejected'], true)) {
                $adopter = (new AdopterModel())->find($application['adopter_id']);
                if (! empty($adopter['email'])) {
                    $this->sendDecisionEmail($adopter['email'], $application['full_name'], $application['animal_name'] ?: $application['cat_description'], $status);
                }
            }
            return redirect()->to('/admin/applications/' . $id)->with('success', 'Application updated.');
        }

        return view('admin/applications/detail', ['title' => 'Application Detail', 'application' => $application, 'assessmentCriteria' => $assessmentService->criteria()]);
    }

    public function catStatus(int $applicationId, int $applicationCatId)
    {
        $itemModel = new ApplicationCatModel();
        $item = $itemModel->where('id', $applicationCatId)->where('application_id', $applicationId)->first();
        $application = (new AdoptionApplicationModel())->find($applicationId);
        $status = (string) $this->request->getPost('status');
        if (! $item || ! $application || ! in_array($status, ['Under Review', 'Approved', 'Rejected'], true)) {
            return redirect()->back()->with('error', 'Invalid cat review request.');
        }
        $cat = (new AnimalModel())->find($item['cat_id']);
        // Load the recipient for either decision. Previously this was only
        // loaded in the Approved branch, so rejection emails were skipped.
        $adopter = (new AdopterModel())->find($application['adopter_id']);
        if ($status === 'Approved' && $cat) {
            if ($adopter) (new AdopterModel())->update($application['adopter_id'], ['name' => $application['full_name'], 'phone' => $application['phone'], 'address' => $application['address'], 'occupation' => $application['occupation'], 'housing_type' => $application['housing_type'], 'next_check_in' => date('Y-m-d', strtotime('+3 days'))]);
            $adoptionModel = new AdoptionModel();
            if (! $adoptionModel->where('adopter_id', $application['adopter_id'])->where('animal_id', $item['cat_id'])->first()) $adoptionModel->insert(['adopter_id' => $application['adopter_id'], 'animal_id' => $item['cat_id'], 'adoption_date' => date('Y-m-d'), 'status' => 'Active']);
            (new AnimalModel())->update($item['cat_id'], ['status' => 'Adopted']);
            (new AnimalJourneyEventModel())->insert(['animal_id' => $item['cat_id'], 'event_type' => 'Adoption', 'title' => 'Adoption approved', 'description' => 'Adoption application approved and post-adoption monitoring started.', 'event_date' => date('Y-m-d')]);
            $this->closeCompetingApplications((int) $item['cat_id'], $applicationId);
        }
        $itemModel->update($applicationCatId, ['status' => $status]);
        $this->syncParentApplicationStatus($applicationId);
        if ($status !== $item['status'] && in_array($status, ['Approved', 'Rejected'], true) && ! empty($adopter['email'] ?? null)) $this->sendDecisionEmail($adopter['email'], $application['full_name'], $cat['name'] ?? 'the selected cat', $status);
        return redirect()->to('/admin/applications/' . $applicationId)->with('success', 'Cat review updated.');
    }

    /** Keeps the application list status consistent with the individual cat decisions. */
    private function syncParentApplicationStatus(int $applicationId): void
    {
        $statuses = array_column((new ApplicationCatModel())->where('application_id', $applicationId)->findAll(), 'status');
        if (in_array('Under Review', $statuses, true)) {
            $parentStatus = 'Under Review';
        } elseif (in_array('Approved', $statuses, true)) {
            // A multi-cat application is approved once at least one selected
            // cat is approved and no cat decision remains pending.
            $parentStatus = 'Approved';
        } elseif (in_array('Unavailable', $statuses, true)) {
            $parentStatus = 'Unavailable';
        } else {
            $parentStatus = 'Rejected';
        }
        (new AdoptionApplicationModel())->update($applicationId, ['status' => $parentStatus]);
    }

    /** Marks pending requests for an adopted cat as unavailable to other applicants. */
    private function closeCompetingApplications(int $catId, int $approvedApplicationId): void
    {
        $itemModel = new ApplicationCatModel();
        $competingItems = $itemModel
            ->where('cat_id', $catId)
            ->where('application_id !=', $approvedApplicationId)
            ->where('status', 'Under Review')
            ->findAll();
        $itemApplicationIds = [];
        foreach ($competingItems as $item) {
            $itemModel->update($item['id'], ['status' => 'Unavailable']);
            $itemApplicationIds[] = (int) $item['application_id'];
        }

        $applicationModel = new AdoptionApplicationModel();
        $legacyApplications = $applicationModel
            ->where('cat_id', $catId)
            ->where('id !=', $approvedApplicationId)
            ->where('status', 'Under Review')
            ->findAll();
        foreach ($legacyApplications as $application) {
            $applicationModel->update($application['id'], ['status' => 'Unavailable']);
        }

        foreach (array_unique($itemApplicationIds) as $applicationId) {
            $this->syncParentApplicationStatus($applicationId);
        }
    }

    public function document(int $applicationId, int $documentId)
    {
        return redirect()->to('/admin/applications/' . $applicationId);
    }

    public function resendDecision(int $applicationId)
    {
        $application = (new AdoptionApplicationModel())
            ->select('adoption_applications.*, animals.name AS animal_name')
            ->join('animals', 'animals.id = adoption_applications.cat_id', 'left')
            ->find($applicationId);
        if (! $application || ! in_array($application['status'], ['Approved', 'Rejected'], true)) {
            return redirect()->back()->with('error', 'A decision email can only be resent for an approved or rejected application.');
        }
        $adopter = (new AdopterModel())->find($application['adopter_id']);
        if (! $adopter || empty($adopter['email'])) return redirect()->back()->with('error', 'This applicant does not have a valid email address.');
        $this->sendDecisionEmail($adopter['email'], $application['full_name'], $application['animal_name'] ?: $application['cat_description'], $application['status']);
        return redirect()->to('/admin/applications/' . $applicationId)->with('success', 'Decision email resent to the applicant.');
    }

    private function sendDecisionEmail(string $recipient, string $name, string $catName, string $status): void
    {
        $approved = $status === 'Approved';
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeCat = htmlspecialchars($catName, ENT_QUOTES, 'UTF-8');
        $title = $approved ? 'Your adoption application was approved!' : 'Update on your adoption application';
        $message = $approved
            ? "Wonderful news — your application to adopt {$safeCat} has been approved. Please sign in to CatCare 360 for the next steps."
            : "Thank you for applying to adopt {$safeCat}. After review, we’re unable to approve this application at this time.";
        $color = $approved ? '#059669' : '#dc2626';
        $email = service('email');
        $config = config('Email');
        $email->setFrom($config->fromEmail ?: 'no-reply@catcare360.org', $config->fromName ?: 'CatCare 360');
        $email->setTo($recipient);
        $email->setSubject($title);
        $email->setMailType('html');
        $email->setMessage("<!doctype html><html><body style=\"margin:0;background:#f4f7fb;font-family:Arial,sans-serif;color:#172033;padding:28px 16px\"><div style=\"max-width:560px;margin:auto;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 8px 28px rgba(29,78,216,.1)\"><div style=\"background:#2563eb;color:#fff;padding:24px 30px;font-weight:bold;font-size:18px\">🐾 CatCare 360</div><div style=\"padding:30px\"><p style=\"margin:0 0 10px;color:#6b7280;font-size:14px\">Application decision</p><h1 style=\"margin:0 0 14px;font-size:24px;color:{$color}\">{$title}</h1><p style=\"line-height:1.6;color:#4b5563\">Hello {$safeName},</p><p style=\"line-height:1.6;color:#4b5563\">{$message}</p><a href=\"" . site_url('adopter/application') . "\" style=\"display:inline-block;margin-top:12px;background:#2563eb;color:#fff;text-decoration:none;padding:12px 18px;border-radius:8px;font-weight:bold;font-size:14px\">View My Application</a></div><div style=\"padding:16px 30px;background:#f8fafc;color:#9ca3af;font-size:12px\">© 2026 CatCare 360</div></div></body></html>");
        if (! $email->send()) {
            log_message('error', 'Application decision email could not be sent to {email}.', ['email' => $recipient]);
        }
    }
}

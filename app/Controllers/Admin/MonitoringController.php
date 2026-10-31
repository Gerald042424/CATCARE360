<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdopterModel;
use App\Models\MonitoringSubmissionModel;
use App\Models\AnimalJourneyEventModel;

class MonitoringController extends BaseController
{
    public function index(): string
    {
        helper('monitoring');
        refreshActiveAdopterMonitoringCompliance();
        $cases = db_connect()->table('adoptions')
            ->select('adoptions.*, adopters.name as adopter_name, adopters.compliance_score, adopters.flag, animals.name as animal_name')
            ->join('adopters', 'adopters.id = adoptions.adopter_id', 'left')
            ->join('animals', 'animals.id = adoptions.animal_id', 'left')
            ->where('adoptions.status', 'Active')
            ->get()->getResultArray();

        $submissions = (new MonitoringSubmissionModel())
            ->select('monitoring_submissions.*, adopters.name AS adopter_name, adoptions.adoption_date, animals.name AS animal_name')
            ->join('adopters', 'adopters.id = monitoring_submissions.adopter_id', 'left')
            ->join('adoptions', 'adoptions.id = monitoring_submissions.adoption_id', 'left')
            ->join('animals', 'animals.id = adoptions.animal_id', 'left')
            ->orderBy('submission_date', 'DESC')
            ->findAll();

        // Keep all three checkpoints belonging to one adoption together. This
        // prevents a busy chronological feed from mixing different families.
        $submittedCases = [];
        foreach ($submissions as $submission) {
            $adoptionId = (int) $submission['adoption_id'];
            if (! isset($submittedCases[$adoptionId])) {
                $submittedCases[$adoptionId] = [
                    'adoption_id' => $adoptionId,
                    'adopter_name' => $submission['adopter_name'] ?: 'Adopter',
                    'animal_name' => $submission['animal_name'] ?: 'Adopted cat',
                    'adoption_date' => $submission['adoption_date'],
                    'checkpoints' => [],
                    'latest_submission_date' => $submission['submission_date'],
                ];
            }
            $submittedCases[$adoptionId]['checkpoints'][$submission['checkpoint']] = $submission;
        }

        return view('admin/monitoring/index', [
            'title' => 'Post-Adoption Monitoring',
            'cases' => $cases,
            'submissions' => $submissions,
            'submittedCases' => array_values($submittedCases),
        ]);
    }

    public function verify(int $submissionId)
    {
        $submissionModel = new MonitoringSubmissionModel();
        $submission = $submissionModel->find($submissionId);
        if ($submission) {
            $adopter = (new AdopterModel())->find($submission['adopter_id']);
            $verified = $adopter && $submission['gps_lat'] && $submission['gps_lng'] && $adopter['address_lat'] && $adopter['address_lng']
                ? verifyGPS((float) $submission['gps_lat'], (float) $submission['gps_lng'], (float) $adopter['address_lat'], (float) $adopter['address_lng'])
                : false;
            $status = $verified ? 'Verified' : 'Flagged';
            $submissionModel->update($submissionId, ['gps_verified' => $verified, 'status' => $status]);
            recalculateAdopterMonitoringCompliance((int) $submission['adopter_id']);
            $this->recordMonitoringTimelineEvent($submission, $status);
            closeMonitoringCaseWhenComplete((int) $submission['adoption_id']);
            if ($submission['status'] !== $status) $this->sendReviewNotification($submission, $status);
        }

        return redirect()->to('/admin/monitoring')->with('success', 'GPS verification completed.');
    }

    public function review(int $submissionId)
    {
        $status = (string) $this->request->getPost('status');
        if (! in_array($status, ['Verified', 'Flagged'], true)) {
            return redirect()->back()->with('error', 'Choose Verified or Flagged.');
        }
        $submissionModel = new MonitoringSubmissionModel();
        $submission = $submissionModel->find($submissionId);
        if (! $submission) {
            return redirect()->back()->with('error', 'Monitoring update not found.');
        }
        $submissionModel->update($submissionId, ['status' => $status]);
        recalculateAdopterMonitoringCompliance((int) $submission['adopter_id']);
        $this->recordMonitoringTimelineEvent($submission, $status);
        closeMonitoringCaseWhenComplete((int) $submission['adoption_id']);
        if ($submission['status'] !== $status) $this->sendReviewNotification($submission, $status);

        return redirect()->to('/admin/monitoring')->with('success', 'Monitoring update reviewed.');
    }

    private function sendReviewNotification(array $submission, string $status): void
    {
        $adopter = (new AdopterModel())->find($submission['adopter_id']);
        if (! $adopter || empty($adopter['email'])) return;

        $animal = db_connect()->table('adoptions')->select('animals.name')->join('animals', 'animals.id = adoptions.animal_id', 'left')->where('adoptions.id', $submission['adoption_id'])->get()->getRowArray();
        $adopterName = esc($adopter['name']);
        $catName = esc($animal['name'] ?? 'your adopted cat');
        $isVerified = $status === 'Verified';
        $heading = $isVerified ? 'Your monitoring update was verified' : 'Your monitoring update needs attention';
        $message = $isVerified
            ? 'Thank you. Your ' . esc($submission['checkpoint']) . ' update for ' . $catName . ' has been verified by the shelter.'
            : 'Your ' . esc($submission['checkpoint']) . ' update for ' . $catName . ' was flagged for staff follow-up. Please check your adoption portal or contact the shelter.';
        $color = $isVerified ? '#059669' : '#dc2626';
        $email = service('email');
        $config = config('Email');
        $email->setFrom($config->fromEmail ?: 'no-reply@catcare360.org', $config->fromName ?: 'CatCare 360');
        $email->setTo($adopter['email']);
        $email->setSubject($heading . ' | CatCare 360');
        $email->setMailType('html');
        $email->setMessage('<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;padding:28px;border:1px solid #e5e7eb;border-radius:16px"><h2 style="color:' . $color . '">' . $heading . '</h2><p>Hello ' . $adopterName . ',</p><p>' . $message . '</p><p><a href="' . site_url('adopter/portal') . '" style="display:inline-block;padding:12px 16px;background:#2563eb;color:#fff;text-decoration:none;border-radius:8px">Open My Adoption</a></p><p style="font-size:12px;color:#6b7280">CatCare 360 post-adoption monitoring</p></div>');
        if (! $email->send()) log_message('error', 'Monitoring review email could not be sent to {email}.', ['email' => $adopter['email']]);
    }

    private function recordMonitoringTimelineEvent(array $submission, string $status): void
    {
        $adoption = db_connect()->table('adoptions')->where('id', $submission['adoption_id'])->get()->getRowArray();
        if (! $adoption) return;
        (new AnimalJourneyEventModel())->insert(['animal_id' => $adoption['animal_id'], 'event_type' => 'Post-Adoption Monitoring', 'title' => strtoupper((string) $submission['checkpoint']) . ' update ' . strtolower($status), 'description' => 'Administrator reviewed the geo-tagged post-adoption monitoring submission.', 'event_date' => date('Y-m-d')]);
    }
}

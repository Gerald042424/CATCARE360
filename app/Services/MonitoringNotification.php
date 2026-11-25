<?php

namespace App\Services;

use App\Models\AdopterModel;
use App\Models\StaffModel;

class MonitoringNotification
{
    public function requestResubmission(array $submission, string $reason): void
    {
        $this->send(
            (int) $submission['adoption_id'], $submission['checkpoint'], (int) $submission['adopter_id'],
            'A new monitoring photo is requested',
            'The community team needs a clearer photo for this check-in. Please open your adoption portal and take a new photo of your cat using the in-app camera.',
            'Resubmit Check-in', site_url('adopter/portal/' . $submission['adoption_id']) . '#updateForm',
            ['Reason' => $reason]
        );
    }

    public function notifyAdmins(int $adoptionId, string $checkpoint, bool $resubmitted): void
    {
        $this->send(
            $adoptionId, $checkpoint, null,
            $resubmitted ? 'A monitoring update was resubmitted' : 'A new monitoring update is ready for review',
            'An adopter submitted a monitoring photo. Open the check-in to review the update.',
            'Review Monitoring Update', site_url('admin/monitoring') . '#monitoring-' . $adoptionId
        );
    }

    private function send(int $adoptionId, string $checkpoint, ?int $adopterId, string $heading, string $intro, string $button, string $url, array $extraDetails = []): void
    {
        try {
            $recipients = $adopterId === null ? (new StaffModel())->activeAdminEmailAddresses()
                : [(new AdopterModel())->find($adopterId)['email'] ?? ''];
            $animal = db_connect()->table('adoptions')->select('animals.name')
                ->join('animals', 'animals.id = adoptions.animal_id', 'left')->where('adoptions.id', $adoptionId)->get()->getRowArray();
            $details = ['Cat' => $animal['name'] ?? 'Adopted cat', 'Check-in' => $checkpoint] + $extraDetails;
            $message = view('emails/layout', [
                'label' => 'Post-adoption monitoring',
                'heading' => $heading,
                'content' => view('emails/return_notification', compact('intro', 'button', 'url', 'details')),
            ]);
        } catch (\Throwable $error) {
            log_message('error', 'Monitoring notification setup failed for adoption {id}: {type}.', ['id' => $adoptionId, 'type' => $error::class]);
            return;
        }

        foreach ($recipients as $recipient) {
            if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) continue;
            try {
                $email = single_service('email');
                $config = config('Email');
                $email->setFrom($config->fromEmail ?: 'no-reply@catcare360.org', $config->fromName ?: 'CatCare 360');
                $email->setTo($recipient);
                $email->setSubject($heading . ' | CatCare 360');
                $email->setMailType('html');
                $email->setMessage($message);
                if ($email->send()) continue;
            } catch (\Throwable $error) {
                log_message('error', 'Monitoring email transport failed: {type}.', ['type' => $error::class]);
            }
            log_message('error', 'Monitoring email could not be sent for adoption {id}.', ['id' => $adoptionId]);
        }
    }
}

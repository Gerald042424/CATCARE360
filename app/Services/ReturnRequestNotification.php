<?php

namespace App\Services;

use App\Models\StaffModel;

class ReturnRequestNotification
{
    public function send(int $adoptionId, bool $urgent): void
    {
        try {
            $recipients = (new StaffModel())->activeAdminEmailAddresses();
            $config = config('Email');
        } catch (\Throwable $exception) {
            log_message('error', 'Return request notification setup failed for adoption {id}: {type}.', [
                'id' => $adoptionId, 'type' => $exception::class,
            ]);
            return;
        }
        if ($recipients === []) {
            log_message('warning', 'No active administrator email was available for return request {id}.', ['id' => $adoptionId]);
            return;
        }

        $subject = ($urgent ? 'Urgent: ' : '') . 'New cat return request | CatCare 360';
        $url = esc(site_url('admin/monitoring/returns') . '#return-' . $adoptionId, 'attr');
        $message = '<p>An adopter submitted a ' . ($urgent ? 'return request marked urgent' : 'request to return an adopted cat') . '.</p>'
            . '<p><a href="' . $url . '">Review the return request</a> in CatCare 360 to see the cat and adopter details and coordinate the handover.</p>';

        foreach ($recipients as $recipient) {
            try {
                $email = single_service('email');
                $email->setFrom($config->fromEmail ?: 'no-reply@catcare360.org', $config->fromName ?: 'CatCare 360');
                $email->setTo($recipient);
                $email->setSubject($subject);
                $email->setMailType('html');
                $email->setMessage($message);
                if ($email->send()) {
                    continue;
                }
            } catch (\Throwable $exception) {
                log_message('error', 'Return request notification encountered {type}.', ['type' => $exception::class]);
            }
            log_message('error', 'Return request notification could not be sent for adoption {id}.', ['id' => $adoptionId]);
        }
    }
}

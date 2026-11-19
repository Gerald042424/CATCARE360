<?php

namespace App\Services;

use App\Models\StaffModel;

class ReturnRequestNotification
{
    public function send(int $adoptionId, bool $urgent): void
    {
        try {
            $recipients = (new StaffModel())->activeAdminEmailAddresses();
            $message = $this->render(
                'Return request',
                $urgent ? 'An urgent cat return request needs review' : 'A cat return request is ready for review',
                'An adopter has requested a handover with McKinley West Cats. Open the request to review the details and coordinate the handover.',
                'Review Return Request',
                site_url('admin/monitoring/returns') . '#return-' . $adoptionId,
            );
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
        foreach ($recipients as $recipient) {
            $this->deliver($adoptionId, $recipient, $subject, $message);
        }
    }

    public function sendHandover(int $adoptionId): bool
    {
        try {
            $request = db_connect()->table('adoption_return_requests r')
                ->select('r.handover_at, r.handover_location, adopters.name AS adopter_name, adopters.email, animals.name AS cat_name')
                ->join('adoptions', 'adoptions.id = r.adoption_id')
                ->join('adopters', 'adopters.id = adoptions.adopter_id')
                ->join('animals', 'animals.id = adoptions.animal_id')
                ->where(['r.adoption_id' => $adoptionId, 'r.status' => 'Handover Scheduled'])
                ->get()->getRowArray();
            if (! $request || ! filter_var($request['email'], FILTER_VALIDATE_EMAIL)) {
                log_message('warning', 'No adopter email was available for handover {id}.', ['id' => $adoptionId]);
                return false;
            }

            $date = new \DateTimeImmutable($request['handover_at'], new \DateTimeZone('Asia/Manila'));
            $message = $this->render(
                'Handover schedule',
                'Your cat return handover is scheduled',
                'Hello ' . $request['adopter_name'] . ', McKinley West Cats has saved the handover schedule below. Please continue caring for your cat until staff receives them.',
                'View Handover Details',
                site_url('adopter/portal/' . $adoptionId) . '#return-request',
                [
                    'Cat' => $request['cat_name'],
                    'Date and time' => $date->format('F j, Y \a\t g:i A') . ' (Philippine time)',
                    'Location' => $request['handover_location'],
                ],
            );

            return $this->deliver($adoptionId, $request['email'], 'Cat return handover schedule | CatCare 360', $message);
        } catch (\Throwable $exception) {
            log_message('error', 'Handover notification setup failed for adoption {id}: {type}.', [
                'id' => $adoptionId, 'type' => $exception::class,
            ]);
            return false;
        }
    }

    private function render(string $label, string $heading, string $intro, string $button, string $url, array $details = []): string
    {
        return view('emails/layout', [
            'label' => $label,
            'heading' => $heading,
            'content' => view('emails/return_notification', compact('intro', 'button', 'url', 'details')),
        ]);
    }

    private function deliver(int $adoptionId, string $recipient, string $subject, string $message): bool
    {
        try {
            $email = single_service('email');
            $config = config('Email');
            $email->setFrom($config->fromEmail ?: 'no-reply@catcare360.org', $config->fromName ?: 'CatCare 360');
            $email->setTo($recipient);
            $email->setSubject($subject);
            $email->setMailType('html');
            $email->setMessage($message);
            if ($email->send()) {
                return true;
            }
        } catch (\Throwable $exception) {
            log_message('error', 'Return notification encountered {type}.', ['type' => $exception::class]);
        }
        log_message('error', 'Return notification could not be sent for adoption {id}.', ['id' => $adoptionId]);
        return false;
    }
}

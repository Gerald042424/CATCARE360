<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\AdoptionReturnService;
use App\Services\ReturnRequestNotification;

class ReturnRequestsController extends BaseController
{
    public function index(): string
    {
        $db = db_connect();
        $enabled = $db->tableExists('adoption_return_requests');
        $requests = $enabled ? $db->table('adoption_return_requests r')
            ->select('r.*, animals.id AS animal_id, animals.name AS cat_name, adopters.name AS adopter_name, adopters.email, adopters.phone')
            ->join('adoptions', 'adoptions.id = r.adoption_id')
            ->join('animals', 'animals.id = adoptions.animal_id', 'left')
            ->join('adopters', 'adopters.id = adoptions.adopter_id', 'left')
            ->orderBy("CASE WHEN r.status IN ('Submitted', 'Handover Scheduled') THEN 0 ELSE 1 END", 'ASC', false)
            ->orderBy('r.urgent', 'DESC')
            ->orderBy('r.updated_at', 'DESC')
            ->get()->getResultArray() : [];

        return view('admin/monitoring/returns', ['title' => 'Return Requests', 'requests' => $requests, 'enabled' => $enabled]);
    }

    public function schedule(int $adoptionId)
    {
        $saved = (new AdoptionReturnService())->schedule(
            $adoptionId,
            (string) $this->request->getPost('handover_at'),
            trim((string) $this->request->getPost('handover_location')),
        );
        $redirect = redirect()->to(site_url('admin/monitoring/returns') . '#return-' . $adoptionId);
        if (! $saved) {
            return $redirect->with('error', 'Could not save the schedule. Confirm the request is open and enter a future date, time, and location.');
        }
        if (! (new ReturnRequestNotification())->sendHandover($adoptionId)) {
            return $redirect->with('error', 'Handover schedule saved, but the email could not be sent. Try saving again or contact the adopter directly.');
        }
        return $redirect->with('success', 'Handover schedule saved and emailed to the adopter.');
    }

    public function cancel(int $adoptionId)
    {
        $cancelled = (new AdoptionReturnService())->cancel($adoptionId);
        return redirect()->to('admin/monitoring/returns')->with(
            $cancelled ? 'success' : 'error',
            $cancelled ? 'Return request cancelled. Adoption remains active.' : 'This request could not be cancelled.',
        );
    }

    public function confirm(int $adoptionId)
    {
        $confirmed = (new AdoptionReturnService())->confirm($adoptionId);
        if ($confirmed) {
            // Refresh the aggregate when any other adoption is still active,
            // or clear the former adopter's last monitoring schedule.
            $adoption = db_connect()->table('adoptions')->where('id', $adoptionId)->get()->getRowArray();
            if (! empty($adoption['adopter_id'])) {
                helper('monitoring');
                recalculateAdopterMonitoringCompliance((int) $adoption['adopter_id']);
            }
        }
        return redirect()->to('admin/monitoring/returns')->with(
            $confirmed ? 'success' : 'error',
            $confirmed ? 'Return confirmed. The cat is now under assessment.' : 'Return could not be confirmed. Check that the request is scheduled and the cat is still recorded as adopted.',
        );
    }
}

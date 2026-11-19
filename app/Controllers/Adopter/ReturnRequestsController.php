<?php

namespace App\Controllers\Adopter;

use App\Controllers\BaseController;
use App\Services\AdoptionReturnService;

class ReturnRequestsController extends BaseController
{
    public function submit(int $adoptionId)
    {
        $user = session('adopter_user');
        $saved = (new AdoptionReturnService())->submit(
            $adoptionId,
            (int) $user['adopter_id'],
            trim((string) $this->request->getPost('reason')),
            trim((string) $this->request->getPost('notes')),
            $this->request->getPost('urgent') === '1',
        );

        $redirect = redirect()->to(site_url('adopter/portal/' . $adoptionId) . '#return-request');
        return $saved
            ? $redirect->with('success', 'Return request submitted. Please wait for staff to arrange the handover.')
            : $redirect->withInput()->with('error', 'Unable to submit the return request. Check the reason and notes, or review any request already open.');
    }

    public function cancel(int $adoptionId)
    {
        $user = session('adopter_user');
        $cancelled = (new AdoptionReturnService())->cancel($adoptionId, (int) $user['adopter_id']);
        return redirect()->to(site_url('adopter/portal/' . $adoptionId) . '#return-request')
            ->with($cancelled ? 'success' : 'error', $cancelled ? 'Return request cancelled.' : 'This request could not be cancelled.');
    }
}

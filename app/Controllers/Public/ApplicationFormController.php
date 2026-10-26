<?php

namespace App\Controllers\Public;

use App\Controllers\BaseController;
use App\Models\AnimalModel;
use App\Models\ApplicationAnswerModel;
use App\Models\ApplicationDocumentModel;
use App\Models\ApplicationModel;

class ApplicationFormController extends BaseController
{
    public function apply(int $catId)
    {
        $animal = (new AnimalModel())->find($catId);
        if (! $animal) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Cat not found.');
        }

        if ($this->request->getMethod() === 'POST') {
            $answers = [
                'pet_experience' => $this->request->getPost('pet_experience'),
                'household_agrees' => $this->request->getPost('household_agrees'),
                'monitoring_commitment' => $this->request->getPost('monitoring_commitment'),
            ];
            $data = [
                'applicant_name' => trim((string) $this->request->getPost('applicant_name')),
                'email' => trim((string) $this->request->getPost('email')),
                'phone' => trim((string) $this->request->getPost('phone')),
                'occupation' => trim((string) $this->request->getPost('occupation')),
                'housing_type' => $this->request->getPost('housing_type'),
                'animal_id' => $catId,
                'status' => 'Pending Review',
                'compatibility_score' => calculateScore((object) $this->request->getPost(), $answers),
                'submitted_at' => date('Y-m-d H:i:s'),
            ];

            $applicationModel = new ApplicationModel();
            $id = $applicationModel->insert($data, true);
            $answerModel = new ApplicationAnswerModel();
            foreach ($answers as $question => $answer) {
                $answerModel->insert(['application_id' => $id, 'question' => $question, 'answer' => $answer]);
            }
            $documentModel = new ApplicationDocumentModel();
            foreach (['gov_id', 'address_proof', 'home_photos', 'selfie_with_id'] as $doc) {
                $documentModel->insert(['application_id' => $id, 'doc_type' => $doc, 'status' => 'Pending']);
            }

            return redirect()->to('/application-status')->with('success', 'Application submitted. Staff will review your documents and answers.');
        }

        return view('public/apply', ['title' => 'Apply to Adopt', 'animal' => $animal]);
    }

    public function status(): string
    {
        return view('public/application_status', ['title' => 'Application Status']);
    }
}

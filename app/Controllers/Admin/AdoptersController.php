<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdopterModel;
use App\Models\AdopterUserModel;

class AdoptersController extends BaseController
{
    public function index(): string
    {
        helper('monitoring');
        $db = db_connect();
        $monitoringSnapshots = refreshActiveAdopterMonitoringCompliance();

        $adopters = $db->table('adopters')->orderBy('created_at', 'DESC')->get()->getResultArray();
        $adoptions = $db->table('adoptions')
            ->select('adoptions.id AS adoption_id, adoptions.adopter_id, adoptions.adoption_date, animals.name AS animal_name')
            ->join('animals', 'animals.id = adoptions.animal_id', 'left')
            ->where('adoptions.status', 'Active')
            ->orderBy('adoptions.adoption_date', 'DESC')
            ->get()->getResultArray();
        $activeAdoptionIds = array_map('intval', array_column($adoptions, 'adoption_id'));
        $submissions = $activeAdoptionIds === [] ? [] : $db->table('monitoring_submissions')
            ->whereIn('adoption_id', $activeAdoptionIds)
            ->get()
            ->getResultArray();

        foreach ($adoptions as &$adoption) {
            $adoptionSubmissions = array_values(array_filter(
                $submissions,
                static fn (array $submission): bool => (int) $submission['adoption_id'] === (int) $adoption['adoption_id'],
            ));
            $snapshot = calculateMonitoringComplianceSnapshot(
                [['id' => $adoption['adoption_id'], 'adoption_date' => $adoption['adoption_date']]],
                $adoptionSubmissions,
            );
            $adoption['compliance_score'] = $snapshot['score'];
            $adoption['monitoring_due_count'] = $snapshot['expected_count'];
            $adoption['monitoring_verified_due_count'] = $snapshot['verified_expected_count'];
            $adoption['next_check_in'] = $snapshot['next_check_in'];
            $adoption['flag'] = $snapshot['flag'];
        }
        unset($adoption);

        foreach ($adopters as &$adopter) {
            $adopter['adopted_cats'] = array_values(array_filter($adoptions, static fn (array $adoption): bool => (int) $adoption['adopter_id'] === (int) $adopter['id']));
            $snapshot = $monitoringSnapshots[(int) $adopter['id']] ?? null;
            $adopter['monitoring_due_count'] = $snapshot['expected_count'] ?? 0;
            $adopter['monitoring_verified_due_count'] = $snapshot['verified_expected_count'] ?? 0;
        }
        unset($adopter);

        return view('admin/adopters/index', ['title' => 'Adopter Management', 'adopters' => $adopters]);
    }

    public function profile(int $id)
    {
        helper('monitoring');
        recalculateAdopterMonitoringCompliance($id);
        $model = new AdopterModel();
        $adopter = $model->find($id);
        if (! $adopter) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Adopter not found.');
        }

        if ($this->request->getMethod() === 'POST') {
            $data = $this->request->getPost(['name', 'email', 'phone', 'address', 'occupation', 'housing_type', 'flag']);
            $data = array_map(static fn ($value) => is_string($value) ? trim($value) : $value, $data);
            if (! empty($data['email']) && ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return redirect()->back()->withInput()->with('error', 'Enter a valid email address.');
            }
            $model->update($id, $data);
            if (! empty($data['email'])) {
                (new AdopterUserModel())->where('adopter_id', $id)->set('email', $data['email'])->update();
            }

            return redirect()->back()->with('success', 'Adopter profile updated.');
        }

        $db = db_connect();
        $adoptions = $db->table('adoptions')->select('adoptions.*, animals.name, animals.breed, animals.image_url')->join('animals', 'animals.id = adoptions.animal_id')->where('adopter_id', $id)->where('adoptions.status', 'Active')->orderBy('adoptions.adoption_date', 'DESC')->get()->getResultArray();
        $requestedAdoptionId = (int) $this->request->getGet('adoption_id');
        $selectedAdoption = null;
        foreach ($adoptions as $adoption) {
            if ($requestedAdoptionId <= 0 || (int) $adoption['id'] === $requestedAdoptionId) {
                $selectedAdoption = $adoption;
                break;
            }
        }
        if ($requestedAdoptionId > 0 && $selectedAdoption === null) {
            return redirect()->to('/admin/adopters/' . $id)->with('error', 'The selected active adoption was not found.');
        }
        $submissions = $selectedAdoption === null ? [] : $db->table('monitoring_submissions')
            ->where('adoption_id', $selectedAdoption['id'])
            ->orderBy('submission_date', 'DESC')
            ->get()
            ->getResultArray();
        $selectedSnapshot = $selectedAdoption === null
            ? calculateMonitoringComplianceSnapshot([], [])
            : calculateMonitoringComplianceSnapshot(
                [['id' => $selectedAdoption['id'], 'adoption_date' => $selectedAdoption['adoption_date']]],
                $submissions,
            );

        return view('admin/adopters/profile', [
            'title' => 'Adopter Profile',
            'adopter' => $adopter,
            'adoptions' => $adoptions,
            'selectedAdoption' => $selectedAdoption,
            'selectedSnapshot' => $selectedSnapshot,
            'submissions' => $submissions,
        ]);
    }
}

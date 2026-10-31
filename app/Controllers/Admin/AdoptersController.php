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
            ->select('adoptions.adopter_id, adoptions.adoption_date, animals.name AS animal_name')
            ->join('animals', 'animals.id = adoptions.animal_id', 'left')
            ->where('adoptions.status', 'Active')
            ->orderBy('adoptions.adoption_date', 'DESC')
            ->get()->getResultArray();

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
        $monitoringSnapshot = recalculateAdopterMonitoringCompliance($id);
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
        $adoptions = $db->table('adoptions')->select('adoptions.*, animals.name, animals.breed, animals.image_url')->join('animals', 'animals.id = adoptions.animal_id')->where('adopter_id', $id)->where('adoptions.status', 'Active')->get()->getResultArray();

        return view('admin/adopters/profile', [
            'title' => 'Adopter Profile',
            'adopter' => $adopter,
            'adoptions' => $adoptions,
            'monitoringDueCount' => $monitoringSnapshot['expected_count'],
            'submissions' => $db->table('monitoring_submissions')->where('adopter_id', $id)->orderBy('submission_date', 'DESC')->get()->getResultArray(),
        ]);
    }
}

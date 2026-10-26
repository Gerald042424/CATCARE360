<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdoptionModel;
use App\Models\AdopterModel;
use App\Models\HomeVisitModel;

class HomeVisitsController extends BaseController
{
    public function index()
    {
        $visits = (new HomeVisitModel())
            ->select('home_visits.*, adopters.name AS adopter_name, animals.name AS animal_name')
            ->join('adopters', 'adopters.id = home_visits.adopter_id')
            ->join('adoptions', 'adoptions.id = home_visits.adoption_id', 'left')
            ->join('animals', 'animals.id = adoptions.animal_id', 'left')
            ->orderBy('scheduled_date', 'DESC')->findAll();
        $adopters = (new AdopterModel())->whereIn('flag', ['WANTED', 'MISSING'])->orderBy('name')->findAll();
        foreach ($adopters as &$adopter) {
            $adopter['adoptions'] = (new AdoptionModel())->select('adoptions.*, animals.name AS animal_name')->join('animals', 'animals.id = adoptions.animal_id')->where('adopter_id', $adopter['id'])->findAll();
        }
        unset($adopter);
        return view('admin/home_visits/index', ['title' => 'Home Visit Follow-ups', 'visits' => $visits, 'flaggedAdopters' => $adopters]);
    }

    public function save()
    {
        $adopterId = (int) $this->request->getPost('adopter_id');
        if (! (new AdopterModel())->find($adopterId)) return redirect()->back()->with('error', 'Choose a valid adopter.');
        (new HomeVisitModel())->insert([
            'adopter_id' => $adopterId,
            'adoption_id' => (int) $this->request->getPost('adoption_id') ?: null,
            'scheduled_date' => $this->request->getPost('scheduled_date') ?: null,
            'status' => 'Scheduled',
            'findings' => trim((string) $this->request->getPost('findings')),
        ]);
        return redirect()->to('/admin/home-visits')->with('success', 'Home visit scheduled.');
    }

    public function update(int $id)
    {
        $status = (string) $this->request->getPost('status');
        if (! in_array($status, ['Scheduled', 'Completed', 'Cancelled'], true)) return redirect()->back()->with('error', 'Choose a valid visit status.');
        (new HomeVisitModel())->update($id, ['status' => $status, 'scheduled_date' => $this->request->getPost('scheduled_date') ?: null, 'findings' => trim((string) $this->request->getPost('findings')), 'outcome' => trim((string) $this->request->getPost('outcome'))]);
        return redirect()->to('/admin/home-visits')->with('success', 'Home visit record updated.');
    }
}

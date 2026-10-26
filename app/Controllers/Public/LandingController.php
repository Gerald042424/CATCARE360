<?php

namespace App\Controllers\Public;

use App\Controllers\BaseController;
use App\Models\AnimalModel;
use App\Models\AdopterModel;
use App\Models\VolunteerModel;

class LandingController extends BaseController
{
    public function index(): string
    {
        return view('public/landing', [
            'title' => 'CatCare 360',
            'stats' => [
                'cats' => (new AnimalModel())->countAllResults(),
                'adoptions' => (new AnimalModel())->where('status', 'Adopted')->countAllResults(),
                'volunteers' => (new VolunteerModel())->where('status !=', 'Inactive')->countAllResults(),
                'compliance' => round((new AdopterModel())->selectAvg('compliance_score')->first()['compliance_score'] ?? 91),
            ],
        ]);
    }
}

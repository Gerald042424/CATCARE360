<?php

namespace App\Controllers\Public;

use App\Controllers\BaseController;
use App\Models\AnimalModel;
use App\Models\VolunteerModel;

class LandingController extends BaseController
{
    public function index(): string
    {
        helper('monitoring');
        $monitoringSnapshots = activeAdopterMonitoringComplianceSnapshots();
        $startedScores = array_column(array_filter($monitoringSnapshots, static fn (array $snapshot): bool => $snapshot['has_started']), 'score');

        return view('public/landing', [
            'title' => 'CatCare 360',
            'stats' => [
                'cats' => (new AnimalModel())->countAllResults(),
                'adoptions' => (new AnimalModel())->where('status', 'Adopted')->countAllResults(),
                'volunteers' => (new VolunteerModel())->where('status !=', 'Inactive')->countAllResults(),
                'compliance' => empty($startedScores) ? null : (int) round(array_sum($startedScores) / count($startedScores)),
            ],
        ]);
    }
}

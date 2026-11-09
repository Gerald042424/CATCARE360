<?php

namespace App\Controllers\Public;

use App\Controllers\BaseController;
use App\Models\AnimalModel;
use App\Models\AnimalPhotoModel;
use App\Models\VolunteerModel;

class LandingController extends BaseController
{
    public function index(): string
    {
        helper('monitoring');
        $animalModel = new AnimalModel();
        $monitoringSnapshots = activeAdopterMonitoringComplianceSnapshots();
        $startedScores = array_column(array_filter($monitoringSnapshots, static fn (array $snapshot): bool => $snapshot['has_started']), 'score');

        return view('public/landing', [
            'title' => 'CatCare 360',
            'stats' => [
                'cats' => $animalModel->countAllResults(),
                'adoptions' => (new AnimalModel())->where('status', 'Adopted')->countAllResults(),
                'volunteers' => (new VolunteerModel())->where('status !=', 'Inactive')->countAllResults(),
                'compliance' => empty($startedScores) ? null : (int) round(array_sum($startedScores) / count($startedScores)),
            ],
            'featuredCats' => (new AnimalModel())
                ->where('status', 'Available')
                ->orderBy('created_at', 'DESC')
                ->findAll(3),
        ]);
    }

    public function about(): string
    {
        return view('public/about', ['title' => 'About Us | CatCare 360']);
    }

    public function faq(): string
    {
        return view('public/faq', ['title' => 'Frequently Asked Questions | CatCare 360']);
    }

    public function contact(): string
    {
        return view('public/contact', ['title' => 'Contact Us | CatCare 360']);
    }

    public function cats(): string
    {
        return view('public/cats', [
            'title' => 'Meet the Cats | CatCare 360',
            'cats' => (new AnimalModel())
                ->where('status', 'Available')
                ->orderBy('created_at', 'DESC')
                ->findAll(),
        ]);
    }

    public function cat(int $catId)
    {
        $cat = (new AnimalModel())
            ->where('id', $catId)
            ->where('status', 'Available')
            ->first();

        if (! $cat) {
            return redirect()->to('/cats')->with('error', 'That cat is no longer available for adoption.');
        }

        return view('public/cat', [
            'title' => $cat['name'] . ' | CatCare 360',
            'cat' => $cat,
            'galleryPhotos' => (new AnimalPhotoModel())
                ->where('animal_id', $catId)
                ->orderBy('created_at', 'DESC')
                ->findAll(),
        ]);
    }
}

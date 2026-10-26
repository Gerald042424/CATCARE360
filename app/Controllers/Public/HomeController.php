<?php

namespace App\Controllers\Public;

use App\Controllers\BaseController;
use App\Models\AnimalModel;

class HomeController extends BaseController
{
    public function index(): string
    {
        return view('public/home', [
            'title' => 'Available Cats',
            'animals' => (new AnimalModel())->where('status', 'Available')->orderBy('created_at', 'DESC')->findAll(),
        ]);
    }
}

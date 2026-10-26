<?php

namespace App\Models;

use CodeIgniter\Model;

class AnimalModel extends Model
{
    protected $table = 'animals';
    protected $allowedFields = ['name', 'species', 'breed', 'age', 'weight', 'gender', 'status', 'stage', 'health', 'intake_date', 'behavior_notes', 'medical_notes', 'image_url', 'color', 'cnvr_status', 'cnvr_catch', 'cnvr_neuter', 'cnvr_vaccinate', 'cnvr_return', 'location'];
    protected $useTimestamps = true;
}

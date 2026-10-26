<?php

namespace App\Models;

use CodeIgniter\Model;

class AdoptionModel extends Model
{
    protected $table = 'adoptions';
    protected $allowedFields = ['adopter_id', 'animal_id', 'adoption_date', 'status', 'monitoring_closed_at'];
    protected $useTimestamps = true;
}

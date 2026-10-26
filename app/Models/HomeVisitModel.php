<?php

namespace App\Models;

use CodeIgniter\Model;

class HomeVisitModel extends Model
{
    protected $table = 'home_visits';
    protected $allowedFields = ['adopter_id', 'adoption_id', 'scheduled_date', 'status', 'findings', 'outcome'];
    protected $useTimestamps = true;
}

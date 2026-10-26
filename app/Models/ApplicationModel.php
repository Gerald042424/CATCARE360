<?php

namespace App\Models;

use CodeIgniter\Model;

class ApplicationModel extends Model
{
    protected $table = 'applications';
    protected $allowedFields = ['applicant_name', 'email', 'phone', 'occupation', 'housing_type', 'animal_id', 'status', 'compatibility_score', 'submitted_at'];
    protected $useTimestamps = true;
}

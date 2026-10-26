<?php

namespace App\Models;

use CodeIgniter\Model;

class AdoptionAssessmentModel extends Model
{
    protected $table = 'adoption_application_assessments';
    protected $allowedFields = ['application_id', 'criterion_key', 'response', 'weight', 'rating', 'rating_reason', 'earned_points', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
}

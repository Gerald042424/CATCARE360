<?php
namespace App\Models;
use CodeIgniter\Model;
class AdoptionAssessmentFlagModel extends Model
{
    protected $table = 'adoption_assessment_flags';
    protected $allowedFields = ['application_id','question_group','status','reason','criterion_key','related_answers','review_status','review_notes','reviewed_by','reviewed_at'];
    protected $useTimestamps = true;
}

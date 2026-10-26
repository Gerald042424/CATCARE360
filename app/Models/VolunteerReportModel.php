<?php
namespace App\Models;
use CodeIgniter\Model;
class VolunteerReportModel extends Model { protected $table='volunteer_reports'; protected $allowedFields=['volunteer_id','task_id','cat_name','health_status','observations','photo_path','gps_lat','gps_lng','gps_accuracy','capture_source','captured_at','submitted_at']; protected $useTimestamps=false; }

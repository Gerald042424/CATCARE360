<?php
namespace App\Models;
use CodeIgniter\Model;
class TaskReportDraftModel extends Model { protected $table='task_report_drafts'; protected $allowedFields=['task_id','volunteer_id','outcome','notes','checklist','updated_at']; protected $useTimestamps=false; }

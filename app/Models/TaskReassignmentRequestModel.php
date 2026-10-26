<?php
namespace App\Models;
use CodeIgniter\Model;
class TaskReassignmentRequestModel extends Model { protected $table='task_reassignment_requests'; protected $allowedFields=['task_id','volunteer_id','reason','comments','status','created_at']; protected $useTimestamps=false; }

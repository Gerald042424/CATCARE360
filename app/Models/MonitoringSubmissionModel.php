<?php

namespace App\Models;

use CodeIgniter\Model;

class MonitoringSubmissionModel extends Model
{
    protected $table = 'monitoring_submissions';
    protected $allowedFields = ['adopter_id', 'adoption_id', 'checkpoint', 'submission_date', 'photo_url', 'health_status', 'behavior_notes', 'gps_lat', 'gps_lng', 'gps_accuracy', 'gps_verified', 'capture_source', 'captured_at', 'device_info', 'user_agent', 'ip_address', 'status'];
    protected $useTimestamps = true;
}

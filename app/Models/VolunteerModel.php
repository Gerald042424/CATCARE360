<?php

namespace App\Models;

use CodeIgniter\Model;

class VolunteerModel extends Model
{
    protected $table = 'volunteers';
    protected $allowedFields = ['name', 'role', 'email', 'phone', 'location', 'status', 'availability', 'availability_start_time', 'availability_end_time', 'notes', 'image_url', 'joined_date', 'tasks_completed', 'pending_tasks', 'notif_email', 'privacy_mode'];
    protected $useTimestamps = true;
}

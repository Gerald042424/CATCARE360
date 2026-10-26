<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskModel extends Model
{
    protected $table = 'tasks';
    protected $allowedFields = ['title', 'description', 'category', 'priority', 'status', 'assigned_to', 'animal_id', 'task_date', 'task_time', 'location'];
    protected $useTimestamps = true;
}

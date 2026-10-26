<?php

namespace App\Models;

use CodeIgniter\Model;

class VolunteerUserModel extends Model
{
    protected $table = 'volunteer_users';
    protected $allowedFields = ['volunteer_id', 'email', 'password_hash'];
    protected $useTimestamps = true;
}

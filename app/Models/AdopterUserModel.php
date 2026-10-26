<?php

namespace App\Models;

use CodeIgniter\Model;

class AdopterUserModel extends Model
{
    protected $table = 'adopter_users';
    protected $allowedFields = ['adopter_id', 'email', 'password_hash', 'first_name', 'last_name', 'status'];
    protected $useTimestamps = true;
}

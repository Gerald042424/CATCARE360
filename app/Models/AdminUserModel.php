<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminUserModel extends Model
{
    protected $table = 'admin_users';
    protected $allowedFields = ['username', 'password_hash', 'name', 'role'];
    protected $useTimestamps = true;
}

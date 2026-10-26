<?php

namespace App\Models;

use CodeIgniter\Model;

class StaffModel extends Model
{
    protected $table = 'staff';
    protected $allowedFields = ['name', 'email', 'password_hash', 'role', 'is_active'];
}

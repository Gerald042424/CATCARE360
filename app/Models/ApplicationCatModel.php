<?php

namespace App\Models;

use CodeIgniter\Model;

class ApplicationCatModel extends Model
{
    protected $table = 'adoption_application_cats';
    protected $allowedFields = ['application_id', 'cat_id', 'status'];
    protected $useTimestamps = true;
}

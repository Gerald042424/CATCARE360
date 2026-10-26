<?php

namespace App\Models;

use CodeIgniter\Model;

class AdopterModel extends Model
{
    protected $table = 'adopters';
    protected $allowedFields = [
        'application_id',
        'first_name',
        'last_name',
        'name',
        'email',
        'phone',
        'address',
        'occupation',
        'employer',
        'housing_type',
        'ownership',
        'landlord_approval',
        'current_pets',
        'vet_info',
        'image_path',
        'status',
        'address_lat',
        'address_lng',
        'compliance_score',
        'next_check_in',
        'total_submissions',
        'overdue_visits',
        'gps_verified_count',
        'flag',
    ];
    protected $useTimestamps = true;
}

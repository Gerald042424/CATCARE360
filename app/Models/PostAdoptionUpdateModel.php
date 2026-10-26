<?php

namespace App\Models;

use CodeIgniter\Model;

class PostAdoptionUpdateModel extends Model
{
    protected $table = 'post_adoption_updates';
    protected $allowedFields = [
        'adopter_id',
        'cat_id',
        'update_type',
        'description',
        'photo_path',
        'latitude',
        'longitude',
        'status',
        'submitted_at',
    ];
    protected $useTimestamps = false;
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class AnimalPhotoModel extends Model
{
    protected $table = 'animal_photos';
    protected $allowedFields = ['animal_id', 'photo_url', 'caption'];
    protected $useTimestamps = true;
}

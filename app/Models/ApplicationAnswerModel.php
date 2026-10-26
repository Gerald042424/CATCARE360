<?php

namespace App\Models;

use CodeIgniter\Model;

class ApplicationAnswerModel extends Model
{
    protected $table = 'application_answers';
    protected $allowedFields = ['application_id', 'question', 'answer'];
}

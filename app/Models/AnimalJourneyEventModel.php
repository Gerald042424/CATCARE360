<?php

namespace App\Models;

use CodeIgniter\Model;

class AnimalJourneyEventModel extends Model
{
    protected $table = 'animal_journey_events';
    protected $allowedFields = ['animal_id', 'event_type', 'title', 'description', 'event_date'];
    protected $useTimestamps = false;
}

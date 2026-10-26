<?php
namespace App\Models;
use CodeIgniter\Model;
class VolunteerApplicationModel extends Model { protected $table='volunteer_applications'; protected $allowedFields=['first_name','last_name','email','phone','address','city','age','available_days','time_slot','hours_per_week','start_date','has_animal_exp','experience_details','roles','motivation','emergency_name','emergency_relation','emergency_phone','status','submitted_at']; }

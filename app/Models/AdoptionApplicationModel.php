<?php

namespace App\Models;

use CodeIgniter\Model;

class AdoptionApplicationModel extends Model
{
    protected $table = 'adoption_applications';
    protected $allowedFields = [
        'application_id',
        'adopter_id',
        'cat_id',
        'cat_description',
        'full_name',
        'address',
        'phone',
        'occupation',
        'pet_ownership_experience',
        'monthly_pet_budget',
        'housing_type',
        'compatibility_score',
        'assessment_score',
        'assessment_status',
        'indoor_living_compliance',
        'humane_housing_compliance',
        'existing_pet_concern',
        'staff_notes',
        'final_decision_reason',
        'assessment_updated_at',
        'lifetime_commitment',
        'secured_space',
        'food_water',
        'vet_bills',
        'spay_neuter',
        'deworming',
        'tick_flea',
        'family_comfortable',
        'love_attention',
        'caretaker_when_away',
        'selfie_path',
        'gov_id_path',
        'address_proof_path',
        'home_photos_path',
        'verification_metadata',
        'ocr_status',
        'ocr_message',
        'status',
        'submitted_at',
    ];
    protected $useTimestamps = false;
}

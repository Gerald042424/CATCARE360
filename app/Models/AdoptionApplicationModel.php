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

    /**
     * Return every cat this adopter has already included in an application.
     *
     * Older applications store the first cat on adoption_applications.cat_id,
     * while current multi-cat applications also use adoption_application_cats.
     * Reading both keeps the eligibility rule correct for all existing data.
     *
     * @return list<int>
     */
    public function appliedCatIdsForAdopter(int $adopterId): array
    {
        if ($adopterId <= 0) {
            return [];
        }

        $applications = $this
            ->select('id, cat_id')
            ->where('adopter_id', $adopterId)
            ->findAll();

        $applicationIds = [];
        $catIds = [];
        foreach ($applications as $application) {
            $applicationIds[] = (int) $application['id'];
            if ((int) ($application['cat_id'] ?? 0) > 0) {
                $catIds[] = (int) $application['cat_id'];
            }
        }

        if ($applicationIds !== []) {
            $links = (new ApplicationCatModel())
                ->select('cat_id')
                ->whereIn('application_id', $applicationIds)
                ->findAll();
            foreach ($links as $link) {
                if ((int) ($link['cat_id'] ?? 0) > 0) {
                    $catIds[] = (int) $link['cat_id'];
                }
            }
        }

        $catIds = array_values(array_unique($catIds));
        sort($catIds);

        return $catIds;
    }

    public function adopterHasAppliedForCat(int $adopterId, int $catId): bool
    {
        return $catId > 0
            && in_array($catId, $this->appliedCatIdsForAdopter($adopterId), true);
    }
}

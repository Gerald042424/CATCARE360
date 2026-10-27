<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/** Client-proposed values. Staff may change these in one place after validation. */
class AdoptionAssessment extends BaseConfig
{

    public array $criteria = [
        'long_term_commitment' => ['label' => 'Long-Term Commitment and Care Readiness', 'weight' => 10.0, 'group' => 'commitment'],
        'housing_permission' => ['label' => 'Housing Status and Permission', 'weight' => 10.0, 'group' => 'housing'],
        'food_water' => ['label' => 'Healthy Food and Clean Water', 'weight' => 8.0, 'group' => 'health'],
        'veterinary_expenses' => ['label' => 'Vaccinations and Veterinary Expenses', 'weight' => 10.0, 'group' => 'health'],
        'spay_neuter_plan' => ['label' => 'Spaying/Neutering Plan', 'weight' => 8.0, 'group' => 'pets'],
        'deworming_schedule' => ['label' => 'Deworming Schedule', 'weight' => 5.0, 'group' => 'health'],
        'tick_flea_prevention' => ['label' => 'Tick and Flea Prevention', 'weight' => 5.0, 'group' => 'health'],
        'family_readiness' => ['label' => 'Family Readiness and Support', 'weight' => 5.0, 'group' => 'commitment'],
        'daily_care' => ['label' => 'Love, Attention, and Daily Care', 'weight' => 8.0, 'group' => 'commitment'],
        'backup_caregiver' => ['label' => 'Backup Caregiver and Contingency Plan', 'weight' => 5.0, 'group' => 'backup'],
        'indoor_living' => ['label' => 'Indoor Living and Escape Prevention', 'weight' => 15.0, 'group' => 'housing'],
        'humane_housing' => ['label' => 'Humane Housing and No Permanent Cage Confinement', 'weight' => 5.0, 'group' => 'housing'],
        'existing_pets' => ['label' => 'Existing Pets and Sterilization Status', 'weight' => 6.0, 'group' => 'pets'],
    ];

    public array $groups = [
        'commitment' => ['title' => '1. Long-Term Commitment, Daily Care, and Household Readiness', 'question' => 'Why do you want to adopt a cat, and how will your household provide safe, consistent, long-term care throughout its life?', 'subquestions' => ['Describe a typical day: feeding, cleaning, play, attention, and rest.', 'Who agrees to the adoption and how will care tasks be shared?', 'What is your plan if work, school, finances, or living arrangements change?', 'How would you handle household concerns while keeping the cat safe?']],
        'housing' => ['title' => '2. Housing, Indoor Living, Escape Prevention, and Humane Space', 'question' => 'Describe your living arrangement, permission to keep a cat, indoor space, escape prevention, and humane free movement without permanent cage confinement.', 'subquestions' => ['Do you own or rent, and what pet permission or rules apply?', 'Where will food, water, litter, rest, and hiding spaces be located?', 'How will you secure doors, windows, balconies, and other openings?', 'How will the cat move, hide, scratch, and play freely, including any temporary separation?']],
        'health' => ['title' => '3. Food, Water, Veterinary Care, and Parasite Prevention', 'question' => 'How will you provide food, clean water, routine and unexpected veterinary care, vaccinations, deworming, and safe flea/tick prevention?', 'subquestions' => ['What feeding and clean-water routine will you follow?', 'How will you budget for checkups, illness, and unexpected vet expenses?', 'How will you follow vaccination and deworming advice?', 'What would you do about fleas or ticks using cat-safe treatment?']],
        'pets' => ['title' => '4. Spaying/Neutering and Existing Pets', 'question' => 'What is your veterinarian-guided spaying/neutering plan, and how will you keep the cat safe if you have other pets?', 'subquestions' => ['When and how will you discuss the plan with a veterinarian?', 'Describe existing pets and their sterilization status, if known.', 'How will you introduce the adopted cat safely?', 'How will you prevent unwanted mating if any cats are not sterilized?']],
        'backup' => ['title' => '5. Backup Caregiver and Emergency Plan', 'question' => 'Who will care for the cat if you travel, become ill, cannot return home, or face an emergency?', 'subquestions' => ['Who specifically can care for the cat?', 'Has the person agreed and can they access supplies and instructions?', 'How will food, water, litter, and medication be provided?', 'What is your second option if the usual caregiver is unavailable?']],
        'reflection' => ['title' => '6. Overall Readiness and Responsible Follow-Through (unweighted)', 'question' => 'What will be most challenging, and what specific steps will you take to handle it responsibly?', 'subquestions' => ['What part of the care plan may be hardest to maintain?', 'What resources, people, or services could help?', 'What would you do first if the cat is ill, stressed, or struggling to adjust?', 'What should the adoption team clarify before deciding?']],
    ];
}

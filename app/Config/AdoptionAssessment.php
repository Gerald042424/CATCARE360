<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/** Client-proposed values. Staff may change these in one place after validation. */
class AdoptionAssessment extends BaseConfig
{
    public array $thresholds = ['recommended' => 80.0, 'further_assessment' => 60.0];

    public array $criteria = [
        'long_term_commitment' => ['label' => 'Long-Term Commitment', 'weight' => 10.0, 'question' => 'Why do you want to adopt a cat, and how will you provide long-term care throughout its life?', 'indicators' => ['Realistic understanding of lifelong ownership', 'Cat-care responsibilities', 'Plan for housing, work, or financial changes']],
        'secure_housing' => ['label' => 'Secure Housing and Landlord Permission', 'weight' => 10.0, 'question' => 'Do you own or rent your home? Describe where the cat will live and, if you are renting, whether pets are permitted.', 'indicators' => ['Suitable living environment', 'Landlord permission when required', 'How the cat will be accommodated']],
        'food_water' => ['label' => 'Healthy Food and Clean Water', 'weight' => 8.0, 'question' => 'How will you provide appropriate cat food, clean drinking water, and a regular feeding routine?', 'indicators' => ['Consistent food supply', 'Clean drinking water', 'Feeding schedule and basic nutritional needs']],
        'veterinary_expenses' => ['label' => 'Vaccinations and Veterinary Expenses', 'weight' => 10.0, 'question' => 'How will you budget for vaccinations, veterinary consultations, and unexpected medical expenses?', 'indicators' => ['Realistic veterinary-expense plan', 'Access to veterinary services', 'Willingness to seek treatment when needed']],
        'spay_neuter_plan' => ['label' => 'Spaying/Neutering Plan', 'weight' => 8.0, 'question' => 'What is your plan for having the adopted cat spayed or neutered when appropriate?', 'indicators' => ['Purpose of spaying or neutering', 'Practical veterinary plan', 'Cat age, health, and veterinary advice']],
        'deworming_schedule' => ['label' => 'Deworming Schedule', 'weight' => 5.0, 'question' => 'How will you manage the cat’s deworming needs in consultation with a veterinarian?', 'indicators' => ['Importance of parasite control', 'Appropriate veterinary schedule', 'Treatment suitable to the cat’s condition']],
        'tick_flea_prevention' => ['label' => 'Tick and Flea Prevention', 'weight' => 5.0, 'question' => 'How will you prevent and manage fleas and ticks safely?', 'indicators' => ['Practical parasite-prevention plan', 'Cat-appropriate products', 'Veterinary advice when needed']],
        'family_readiness' => ['label' => 'Family Readiness and Support', 'weight' => 5.0, 'question' => 'How do the people living with you feel about adopting a cat, and how will you handle any concerns?', 'indicators' => ['Household members are aware', 'Shared responsibilities', 'Allergies, concerns, and compatibility']],
        'daily_care' => ['label' => 'Love, Attention, and Daily Care', 'weight' => 8.0, 'question' => 'How will you provide attention, play, companionship, hygiene, rest, and enrichment for the cat?', 'indicators' => ['Realistic daily-care routine', 'Physical, emotional, and behavioral needs', 'Play, rest, exploration, and interaction']],
        'backup_caregiver' => ['label' => 'Backup Caregiver', 'weight' => 5.0, 'question' => 'Who can care for the cat if you travel, become ill, or cannot return home?', 'indicators' => ['Potential backup caregiver', 'Care arranged during absence', 'Continuing food, water, hygiene, and supervision']],
        'indoor_living' => ['label' => 'Safe Living Environment and Escape Prevention', 'weight' => 15.0, 'question' => 'Describe where the adopted cat will stay, whether indoors, outdoors, or in a combination of both, and explain how you will keep the cat safe from potential hazards, injuries, and getting lost.', 'indicators' => ['Intended indoor, outdoor, or combined environment', 'Hazards identified and minimized', 'Protection from traffic, aggressive animals, dangerous substances, and relevant risks', 'Measures to prevent getting lost or accidental escape', 'Shelter, clean water, resting areas, and daily care']],
        'humane_housing' => ['label' => 'Humane Housing and No Permanent Cage Confinement', 'weight' => 5.0, 'question' => 'How will you provide a safe and suitable living environment where the cat can move freely, rest, play, and express its natural behaviors without being permanently confined to a cage?', 'indicators' => ['Safe space for movement, rest, and activity', 'Shelter, resting areas, and enrichment', 'Physical and behavioral needs supported', 'No permanent cage confinement as primary housing']],
        'existing_pets' => ['label' => 'Existing Pets and Sterilization Status', 'weight' => 6.0, 'question' => 'Do you currently have other cats or pets? Describe their sterilization status, how you would introduce the adopted cat safely, and how you would prevent unwanted breeding.', 'indicators' => ['Existing pets and care arrangements disclosed', 'Gradual, safe introduction plan', 'Unwanted breeding prevention', 'Veterinary guidance when needed']],
    ];
}

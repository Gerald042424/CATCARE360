<?php

namespace App\Services;

use App\Models\AdoptionAssessmentModel;

class AdoptionAssessmentService
{
    /** Each original criterion draws evidence from specified main/subanswers, not one whole group answer. */
    private array $criterionSources = [
        'long_term_commitment' => ['commitment_main', 'commitment_sub_3'],
        'daily_care' => ['commitment_sub_1'],
        'family_readiness' => ['commitment_sub_2', 'commitment_sub_4'],
        'housing_permission' => ['housing_main', 'housing_sub_1'],
        'indoor_living' => ['housing_sub_2', 'housing_sub_3'],
        'humane_housing' => ['housing_main', 'housing_sub_4'],
        'food_water' => ['health_main', 'health_sub_1'],
        'veterinary_expenses' => ['health_main', 'health_sub_2'],
        'deworming_schedule' => ['health_sub_3'],
        'tick_flea_prevention' => ['health_sub_4'],
        'spay_neuter_plan' => ['pets_main', 'pets_sub_1', 'pets_sub_4'],
        'existing_pets' => ['pets_sub_2', 'pets_sub_3'],
        'backup_caregiver' => ['backup_main', 'backup_sub_1', 'backup_sub_2', 'backup_sub_3', 'backup_sub_4'],
    ];
    public function criteria(): array
    {
        return config('AdoptionAssessment')->criteria;
    }

    /** The six consolidated applicant-question groups. */
    public function groups(): array
    {
        return config('AdoptionAssessment')->groups;
    }

    public function weightTotal(): float
    {
        return round(array_sum(array_column($this->criteria(), 'weight')), 2);
    }

    public function earnedPoints(?int $rating, float $weight): float
    {
        return $rating === null ? 0.0 : round(($rating / 100) * $weight, 2);
    }

    public function category(?float $score, bool $complete): string
    {
        if (! $complete || $score === null) return 'Provisional — incomplete assessment';
        return 'Completed — awaiting administrator decision';
    }

    /** A triage flag for staff; it is not an automatic approval or rejection. */
    public function inferredWelfareStatus(string $answer, string $type): string
    {
        $answer = strtolower(trim($answer));
        if ($answer === '') return 'Pending Staff Review';
        if ($type === 'humane' && $this->proposesPermanentCageConfinement($answer)) return 'Needs Clarification';
        if ($type === 'safe_living' && preg_match('/\b(secure|supervis|fence|enclos|screen|collar|microchip|traffic|hazard|escape|door|window|shelter)\b/', $answer)) return 'Meets Requirements';
        if ($type === 'humane' && preg_match('/\b(free roam|space|play|hide|explore|move)\b/', $answer)) return 'Meets Requirements';
        return 'Needs Clarification';
    }

    public function hasExistingPetConcern(string $answer): bool
    {
        return (bool) preg_match('/\b(unneutered|unspayed|not (?:yet )?neutered|not (?:yet )?spayed|intact)\b/i', $answer);
    }

    /** Documented phrase rules only; flags prompt staff review and never change a score or decision. */
    public function flags(array $answers): array
    {
        $get = static fn (string $group): string => strtolower(trim((string) ($answers[$group] ?? '')));
        $flags = [];
        foreach (['housing' => 'housing_permission', 'health' => 'food_water', 'backup' => 'backup_caregiver'] as $group => $criterion) {
            if ($get($group) === '') $flags[] = [$group, 'Clarification Recommended', 'Required answer is missing.', $criterion];
        }
        if ($get('housing') && ! preg_match('/permission|landlord|owner|rent|own|upa|pahintulot|may-ari/i', $get('housing'))) $flags[] = ['housing', 'Clarification Recommended', 'Housing permission or applicable housing rules are unclear.', 'housing_permission'];
        $housing = $get('housing');
        $permanentCage = preg_match('/cage forever|permanent(?:ly)? (?:in |keep.*)?cage|always (?:in |kept.*)?cage|lagi.*kulungan/i', $housing);
        $rejectsPermanentCage = preg_match('/\b(no|not|never|without)\b.{0,40}\bcage\b|hindi.{0,40}kulungan/i', $housing);
        if ($permanentCage && ! $rejectsPermanentCage) $flags[] = ['housing', 'Major Contradiction', 'Answer appears to rely on permanent cage confinement; administrator review is required.', 'humane_housing'];
        if ($get('housing') && ! preg_match('/door|window|screen|balcony|escape|secure|pinto|bintana|makatakas/i', $get('housing'))) $flags[] = ['housing', 'Clarification Recommended', 'Escape-prevention measures are not described.', 'indoor_living'];
        if ($get('health') && ! preg_match('/vet|clinic|veterinarian|beterinaryo|budget|emergency|gastos/i', $get('health'))) $flags[] = ['health', 'Clarification Recommended', 'Veterinary-care or unexpected-expense plan is unclear.', 'veterinary_expenses'];
        if ($get('health') && ! preg_match('/food|feed|pagkain|water|tubig/i', $get('health'))) $flags[] = ['health', 'Clarification Recommended', 'Food or clean-water plan is unclear.', 'food_water'];
        if ($get('backup') && ! preg_match('/friend|family|caregiver|sitter|pamilya|kaibigan|kamag-anak/i', $get('backup'))) $flags[] = ['backup', 'Clarification Recommended', 'Backup caregiver or suitable alternative is unclear.', 'backup_caregiver'];
        return $flags;
    }

    /** Explicit comparisons only; wording differences alone are never a contradiction. */
    public function consistencyFlags(array $post): array
    {
        $value = static fn (string $key): string => strtolower(trim((string) ($post['assessment_' . $key] ?? '')));
        $flags = [];
        $housingMain = $value('housing_main'); $escapePlan = $value('housing_sub_3');
        if (preg_match('/indoor|inside|loob/i', $housingMain) && preg_match('/unsupervised|roam.*outside|labas.*walang bantay/i', $escapePlan)) {
            $flags[] = ['housing', 'Major Contradiction', 'The Question 2 main answer describes indoor living while Question 2, Subquestion 3 describes unsupervised outdoor roaming.', 'indoor_living', "Question 2 — Housing, Main Answer:\n{$housingMain}\n\nQuestion 2 — Housing, Subquestion 3 (Escape Prevention):\n{$escapePlan}"];
        }
        $pets = $value('pets_sub_2'); $introduction = $value('pets_sub_3');
        if (preg_match('/\b(no|none|wala)\b.{0,25}\b(pets?|cats?|dogs?|pusa|aso)\b/i', $pets) && preg_match('/introduc|ipakilala|separate|hiwalay/i', $introduction)) {
            $flags[] = ['pets', 'Clarification Recommended', 'Question 4 answers about existing pets and introductions may need clarification.', 'existing_pets', "Question 4 — Spaying/Neutering and Existing Pets, Subquestion 2 (Existing Pets):\n{$pets}\n\nQuestion 4 — Spaying/Neutering and Existing Pets, Subquestion 3 (Safe Introduction):\n{$introduction}"];
        }
        return $flags;
    }

    public function createResponses(int $applicationId, array $post): void
    {
        $model = new AdoptionAssessmentModel();
        foreach ($this->criteria() as $key => $criterion) {
            $responseParts = [];
            foreach ($this->criterionSources[$key] ?? [] as $source) {
                [$group, $part] = explode('_', $source, 2);
                $label = $part === 'main' ? 'Main answer' : 'Subquestion ' . substr($part, 4);
                $responseParts[] = $label . ': ' . trim((string) ($post['assessment_' . $source] ?? ''));
            }
            $response = implode("\n\n", $responseParts);
            $assessment = $this->automaticAssessment($key, $response);
            $model->insert(['application_id' => $applicationId, 'criterion_key' => $key, 'response' => $response, 'weight' => $criterion['weight'], 'rating' => $assessment['rating'], 'rating_reason' => $assessment['reason'], 'earned_points' => $this->earnedPoints($assessment['rating'], (float) $criterion['weight'])]);
        }
        $reflectionParts = ['Main answer: ' . trim((string) ($post['assessment_reflection_main'] ?? ''))];
        foreach (($this->groups()['reflection']['subquestions'] ?? []) as $index => $_subquestion) $reflectionParts[] = 'Subquestion ' . ($index + 1) . ': ' . trim((string) ($post['assessment_reflection_sub_' . ($index + 1)] ?? ''));
        $model->insert(['application_id' => $applicationId, 'criterion_key' => 'overall_reflection', 'response' => implode("\n\n", $reflectionParts), 'weight' => 0, 'rating' => null, 'rating_reason' => 'Unweighted reflection and clarification check.', 'earned_points' => 0]);
    }

    /**
     * Deterministic, criterion-level evidence rubric. Each check is tied to a
     * particular main answer or subquestion and has explicit points totalling
     * 100. It deliberately does not score grammar, answer length, or a vague
     * "positive" word elsewhere in the consolidated question.
     */
    public function automaticAssessment(string $key, string $response): array
    {
        $parts = $this->answerParts($response);
        $all = strtolower($response);
        if (trim($response) === '') return ['rating' => 0, 'reason' => 'No relevant answer was submitted (0/100 evidence points).'];
        if ($key === 'humane_housing' && $this->proposesPermanentCageConfinement($all)) return ['rating' => 0, 'reason' => 'Permanent cage confinement is described (0/100 for humane housing).'];

        $rubrics = $this->evidenceRubrics();
        $score = 0; $credited = []; $missing = [];
        foreach ($rubrics[$key] ?? [] as $check) {
            $text = $parts[$check['part']] ?? '';
            if ($this->matchesAny($text, $check['patterns'])) {
                $score += $check['points'];
                $credited[] = $check['label'];
            } else $missing[] = $check['label'];
        }

        // These are safety/evidence limits for the affected criterion, not a
        // penalty applied by the separate consistency-flag feature.
        if ($key === 'indoor_living' && preg_match('/unsupervised|walang bantay|gumala.*labas|roam.*outside/i', $parts['sub_3'] ?? '')) {
            $score = min($score, 35);
            $credited = array_values(array_diff($credited, ['supervised or enclosed safety plan']));
            $missing[] = 'safe supervised or secured outdoor plan';
        }
        if ($key === 'existing_pets') {
            $noPets = $this->matchesAny($parts['sub_2'] ?? '', ['\b(no|none|wala)\b.{0,30}\b(pets?|cats?|dogs?|pusa|aso)\b']);
            $existingPetPlan = $this->matchesAny($parts['sub_3'] ?? '', ['existing (?:cat|pet)|may (?:pusa|aso|alaga)']);
            if ($noPets && ! $existingPetPlan) {
                $score = 100;
                $credited = ['clear statement that there are no existing pets'];
                $missing = [];
            } elseif ($noPets && $existingPetPlan) {
                $score = min($score, 35);
                $credited = ['existing-pet status requires clarification'];
                $missing[] = 'clear, non-conflicting existing-pet status';
            }
        }
        $score = max(0, min(100, $score));
        $reason = 'Evidence rubric: ' . $score . '/100. Credited: ' . ($credited ? implode(', ', array_slice($credited, 0, 4)) : 'none') . '.';
        if ($missing) $reason .= ' Still needed: ' . implode(', ', array_slice(array_unique($missing), 0, 3)) . '.';
        return ['rating' => $score, 'reason' => $reason];
    }

    /** Backwards-compatible score accessor used by existing admin refresh actions. */
    public function automaticRating(string $key, string $answer): int
    {
        return $this->automaticAssessment($key, $answer)['rating'];
    }

    public function automaticRatingReason(string $key, string $answer): string
    {
        return $this->automaticAssessment($key, $answer)['reason'];
    }

    private function answerParts(string $response): array
    {
        $parts = [];
        if (preg_match_all('/^(Main answer|Subquestion\s+(\d+)):\s*(.*?)(?=^Main answer:|^Subquestion\s+\d+:|\z)/msi', $response, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) $parts[strtolower($match[1]) === 'main answer' ? 'main' : 'sub_' . $match[2]] = strtolower(trim($match[3]));
        }
        return $parts;
    }

    private function matchesAny(string $text, array $patterns): bool
    {
        foreach ($patterns as $pattern) if (preg_match('/' . str_replace('/', '\\/', $pattern) . '/iu', $text)) return true;
        return false;
    }

    /** @return array<string,array<int,array{part:string,points:int,label:string,patterns:array<int,string>}>> */
    private function evidenceRubrics(): array
    {
        return [
            'long_term_commitment' => [
                ['part'=>'main','points'=>25,'label'=>'long-term commitment','patterns'=>['long.?term|lifetime|habambuhay|pangmatagalan|habang buhay']],
                ['part'=>'main','points'=>20,'label'=>'daily responsibility','patterns'=>['responsib|care|alaga|pananagutan']],
                ['part'=>'main','points'=>15,'label'=>'time or financial resources','patterns'=>['oras|time|budget|gastos|pera|financial']],
                ['part'=>'sub_3','points'=>20,'label'=>'plan for life changes','patterns'=>['magbago|lumipat|change|move|schedule|work|trabaho']],
                ['part'=>'sub_3','points'=>20,'label'=>'practical continuity step','patterns'=>['pet.?friendly|backup|budget|pamilya|family|hahanap']],
            ],
            'daily_care' => [
                ['part'=>'sub_1','points'=>20,'label'=>'feeding routine','patterns'=>['feed|pakain|pagpapakain|umaga|gabi']], ['part'=>'sub_1','points'=>15,'label'=>'clean water','patterns'=>['water|tubig']],
                ['part'=>'sub_1','points'=>25,'label'=>'litter or hygiene','patterns'=>['litter|buhangin|linis|clean']], ['part'=>'sub_1','points'=>20,'label'=>'play or attention','patterns'=>['play|laro|attention|pansin']], ['part'=>'sub_1','points'=>20,'label'=>'rest or enrichment','patterns'=>['rest|pahinga|enrichment|scratch']],
            ],
            'family_readiness' => [
                ['part'=>'sub_2','points'=>30,'label'=>'household awareness/support','patterns'=>['pamilya|family|household|sang.?ayon|agree|pumayag|alam']], ['part'=>'sub_2','points'=>25,'label'=>'clear care responsibility','patterns'=>['primary|ako ang|share|tutulong|responsib']], ['part'=>'sub_4','points'=>45,'label'=>'safe response to household concern','patterns'=>['kausap|talk|safe|adjust|solution|concern']],
            ],
            'housing_permission' => [
                ['part'=>'sub_1','points'=>25,'label'=>'ownership or rental status','patterns'=>['owner|own|owned|rent|upa|nangungupahan']], ['part'=>'sub_1','points'=>45,'label'=>'permission or applicable rule','patterns'=>['pumayag|permission|allow|pahintulot|may.?ari|rules']], ['part'=>'main','points'=>30,'label'=>'identified safe living space','patterns'=>['bahay|home|room|space|titira|ligtas']],
            ],
            'food_water' => [
                ['part'=>'main','points'=>20,'label'=>'appropriate food','patterns'=>['food|pagkain|pakain']], ['part'=>'main','points'=>15,'label'=>'clean water','patterns'=>['water|tubig']], ['part'=>'sub_1','points'=>20,'label'=>'feeding schedule','patterns'=>['umaga|gabi|schedule|araw.?araw|daily']], ['part'=>'sub_1','points'=>20,'label'=>'water availability','patterns'=>['water|tubig']], ['part'=>'sub_1','points'=>25,'label'=>'care while away','patterns'=>['lakad|away|wala|sapat|iwan']],
            ],
            'veterinary_expenses' => [
                ['part'=>'main','points'=>25,'label'=>'vaccination or routine vet care','patterns'=>['vaccin|bakuna|checkup|vet|clinic']], ['part'=>'main','points'=>25,'label'=>'emergency-care plan','patterns'=>['emergency|biglaan|unexpected|sakit']], ['part'=>'sub_2','points'=>30,'label'=>'budget or savings','patterns'=>['budget|ipon|save|gastos|pera']], ['part'=>'sub_2','points'=>20,'label'=>'identified veterinary access','patterns'=>['vet|clinic|beterinaryo']],
            ],
            'deworming_schedule' => [
                ['part'=>'sub_3','points'=>40,'label'=>'deworming plan','patterns'=>['deworm|bulate|worm']], ['part'=>'sub_3','points'=>30,'label'=>'schedule or tracking','patterns'=>['schedule|calendar|regular|iskedyul']], ['part'=>'sub_3','points'=>30,'label'=>'veterinarian guidance','patterns'=>['vet|beterinaryo|payo|recommend']],
            ],
            'tick_flea_prevention' => [
                ['part'=>'sub_4','points'=>30,'label'=>'flea or tick response','patterns'=>['flea|tick|pulgas|garapata']], ['part'=>'sub_4','points'=>35,'label'=>'cat-safe treatment','patterns'=>['cat.?safe|ligtas.*pusa|safe.*cat']], ['part'=>'sub_4','points'=>35,'label'=>'veterinary or environmental follow-through','patterns'=>['vet|beterinaryo|clean|linis|treatment|gamot']],
            ],
            'spay_neuter_plan' => [
                ['part'=>'main','points'=>30,'label'=>'spay/neuter plan','patterns'=>['spay|neuter|kapon|sterili']], ['part'=>'main','points'=>25,'label'=>'veterinary consultation','patterns'=>['vet|clinic|beterinaryo']], ['part'=>'main','points'=>25,'label'=>'age/health-appropriate timing','patterns'=>['age|edad|health|kalusugan|timing']], ['part'=>'sub_4','points'=>20,'label'=>'unwanted-mating prevention','patterns'=>['hiwalay|separate|prevent|avoid|unwanted|maiwasan']],
            ],
            'existing_pets' => [
                ['part'=>'sub_2','points'=>25,'label'=>'existing-pet details','patterns'=>['pet|cat|dog|pusa|aso|wala']], ['part'=>'sub_2','points'=>25,'label'=>'sterilization status','patterns'=>['spay|neuter|kapon|sterili|wala']], ['part'=>'sub_3','points'=>30,'label'=>'gradual introduction','patterns'=>['gradual|dahan.?dahan|introduc|ipakilala']], ['part'=>'sub_3','points'=>20,'label'=>'safe separation or supervision','patterns'=>['hiwalay|separate|safe|supervis']],
            ],
            'backup_caregiver' => [
                ['part'=>'main','points'=>20,'label'=>'primary backup plan','patterns'=>['ate|family|friend|neighbor|caregiver|pamilya|kaibigan']], ['part'=>'sub_1','points'=>15,'label'=>'specific caregiver','patterns'=>['ate|family|friend|neighbor|caregiver|pamilya|kaibigan']], ['part'=>'sub_2','points'=>20,'label'=>'agreement and care access','patterns'=>['pumayag|agree|supplies|instructions|alam']], ['part'=>'sub_3','points'=>20,'label'=>'continued basic care','patterns'=>['food|water|tubig|litter|medication|gamot']], ['part'=>'sub_4','points'=>25,'label'=>'second emergency option','patterns'=>['second|neighbor|kaibigan|family|pamilya|option']],
            ],
            'indoor_living' => [
                ['part'=>'sub_2','points'=>25,'label'=>'identified cat areas','patterns'=>['living room|bedroom|room|loob|area']], ['part'=>'sub_2','points'=>25,'label'=>'basic indoor facilities','patterns'=>['food|water|litter|bed|taguan|hide']], ['part'=>'sub_3','points'=>35,'label'=>'secured openings','patterns'=>['door|window|balcony|pinto|bintana|screen|bakod']], ['part'=>'sub_3','points'=>15,'label'=>'supervised or enclosed safety plan','patterns'=>['supervis|may\\s+bantay|enclos|secure|ligtas']],
            ],
            'humane_housing' => [
                ['part'=>'main','points'=>20,'label'=>'safe indoor/usable space','patterns'=>['indoor|loob|bahay|space|room']], ['part'=>'sub_4','points'=>30,'label'=>'no permanent cage confinement','patterns'=>['hindi.*(?:permanent|lagi).*(?:kulungan|ikukulong|ikulong)|no.*permanent.*cage|not.*permanent.*cage']], ['part'=>'sub_4','points'=>25,'label'=>'free movement/rest/hiding','patterns'=>['malaya|move|galaw|rest|pahinga|tago|hide']], ['part'=>'sub_4','points'=>25,'label'=>'play or scratching enrichment','patterns'=>['play|laro|scratch|scratching']],
            ],
        ];
    }

    private function proposesPermanentCageConfinement(string $answer): bool
    {
        $mentionsPermanentConfinement = preg_match('/\b(permanent(?:ly)? (?:in |keep.*)?cage|cage forever|always (?:in |kept.*)cage)\b/', $answer);
        $rejectsPermanentConfinement = preg_match('/\b(no|not|never|without)\b.{0,30}\bcage\b/', $answer);
        return (bool) $mentionsPermanentConfinement && ! $rejectsPermanentConfinement;
    }

    /** @return array{score:?float,complete:bool,missing:array<int,string>} */
    public function calculate(array $assessments): array
    {
        $score = 0.0; $missing = [];
        $byKey = array_column($assessments, null, 'criterion_key');
        foreach ($this->criteria() as $key => $criterion) {
            $assessment = $byKey[$key] ?? null;
            if ($assessment === null) {
                $missing[] = $key;
                continue;
            }
            if (trim((string) ($assessment['response'] ?? '')) === '' || $assessment['rating'] === null || $assessment['rating'] === '' || ! is_numeric($assessment['rating']) || (int) $assessment['rating'] < 0 || (int) $assessment['rating'] > 100) {
                $missing[] = $key;
                continue;
            }
            $score += $this->earnedPoints((int) $assessment['rating'], (float) ($assessment['weight'] ?? $criterion['weight']));
        }
        return ['score' => $missing ? null : round($score, 2), 'complete' => $missing === [], 'missing' => $missing];
    }
}

<?php

namespace App\Services;

use App\Models\AdoptionAssessmentModel;

class AdoptionAssessmentService
{
    public function criteria(): array
    {
        return config('AdoptionAssessment')->criteria;
    }

    public function thresholds(): array
    {
        return config('AdoptionAssessment')->thresholds;
    }

    public function weightTotal(): float
    {
        return round(array_sum(array_column($this->criteria(), 'weight')), 2);
    }

    public function earnedPoints(?int $rating, float $weight): float
    {
        return $rating === null ? 0.0 : round(($rating / 4) * $weight, 2);
    }

    public function category(?float $score, bool $complete): string
    {
        if (! $complete || $score === null) return 'Provisional — incomplete assessment';
        $thresholds = $this->thresholds();
        if ($score >= $thresholds['recommended']) return 'Recommended for Approval Review';
        if ($score >= $thresholds['further_assessment']) return 'Needs Further Assessment';
        return 'Not Yet Sufficiently Demonstrated';
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

    public function createResponses(int $applicationId, array $post): void
    {
        $model = new AdoptionAssessmentModel();
        foreach ($this->criteria() as $key => $criterion) {
            $response = trim((string) ($post['assessment_' . $key] ?? ''));
            $rating = $this->automaticRating($key, $response);
            $model->insert(['application_id' => $applicationId, 'criterion_key' => $key, 'response' => $response, 'weight' => $criterion['weight'], 'rating' => $rating, 'rating_reason' => 'Automatic score based on the submitted care-plan indicators.', 'earned_points' => $this->earnedPoints($rating, (float) $criterion['weight'])]);
        }
    }

    /**
     * Indicator-based preliminary rubric. It counts distinct care-plan ideas,
     * never essay length, grammar, or English fluency. Staff may still use
     * professional judgment and request clarification for vague answers.
     */
    public function automaticRating(string $key, string $answer): int
    {
        $answer = strtolower(trim($answer));
        if ($answer === '') return 0;
        if ($key === 'humane_housing' && $this->proposesPermanentCageConfinement($answer)) return 1;
        if ($key === 'existing_pets' && preg_match('/\b(no|none|do not have|don.t have|without)\b.{0,20}\b(pets?|cats?|animals?)\b/', $answer)) return 4;
        $signals = [
            'long_term_commitment' => ['lifetime|long term|years|commit|habambuhay|pangmatagalan|habang buhay|aalagaan', 'care|responsibilit|alaga|responsibilidad|pananagutan|sasagot', 'future|housing|work|financial|budget|change|kung.*(?:lumipat|magbago)|pag(?:lipat|bago)|trabaho|kita|pera|gastos'],
            'secure_housing' => ['own|rent|sarili(?:ng)? bahay|nangungupahan|upa', 'landlord|permission|allow|may-ari|pahintulot', 'safe|space|room|accommodat|live|ligtas|espasyo|silid|titira'],
            'food_water' => ['food|feed|pagkain|pakain', 'water|clean|tubig|malinis', 'daily|routine|schedule|regular|araw-araw|oras'],
            'veterinary_expenses' => ['budget|save|afford|financial|ipon|pera|gastos', 'vet|clinic|consult|vaccine|beterinaryo|bakuna', 'emergency|unexpected|treatment|biglaan|gamot'],
            'spay_neuter_plan' => ['spay|neuter|sterili|kapon', 'vet|clinic|appoint|schedule|beterinaryo|iskedyul', 'age|health|recommend|when appropriate|edad|kalusugan|payo'],
            'deworming_schedule' => ['deworm|parasite|worm|bulate', 'vet|clinic|recommend|beterinaryo|payo', 'schedule|regular|condition|medicine|iskedyul|gamot|kalagayan'],
            'tick_flea_prevention' => ['tick|flea|parasite|garapata|pulgas', 'prevent|regular|treatment|iwas|pag-iwas|gamot', 'cat.?safe|cat.?appropriate|vet|clinic|ligtas.*pusa|beterinaryo'],
            'family_readiness' => ['family|household|people living|pamilya|kasama.*bahay', 'agree|aware|support|concern|sang-ayon|alam|suporta|alalahanin', 'allerg|responsibilit|compatib|responsibilidad|pagkakasundo'],
            'daily_care' => ['daily|routine|hygiene|clean|litter|araw-araw|linis|buhangin', 'physical|emotional|behavior|companionship|attention|pisikal|emosyonal|pansin|samahan', 'play|rest|explor|enrichment|interact|laro|pahinga|galaw'],
            'backup_caregiver' => ['family|friend|sitter|caregiver|pamilya|kamag-anak|kaibigan|magbabantay', 'travel|away|ill|absence|biyahe|lalakbay|may sakit|wala', 'food|water|hygiene|supervis|care|pagkain|tubig|linis|alaga|bantay'],
            'indoor_living' => ['\b(?:indoor|inside|outdoor|outside|combined|both|loob|labas|bahay)\w*\b', '\b(?:hazard|danger|toxic|chemical|traffic|vehicle|unsafe|opening|panganib|lason|sasakyan)\w*\b', '\b(?:secure|supervis|fenc|enclos|screen|door|window|balcony|ligtas|bakod|pinto|bintana|bantay)\w*\b', '\b(?:escape|lost|microchip|collar|identif|makatakas|mawala|kwelyo)\w*\b', '\b(?:shelter|shade|rest|water|silungan|lilim|pahinga|tubig)\w*\b'],
            'humane_housing' => ['space|move|free roam|espasyo|malayang.*galaw', 'shelter|rest|bed|hide|silungan|pahinga|tulugan|taguan', 'play|enrichment|scratch|explore|laro|galugad', 'no permanent cage|not.*permanent.*cage|hindi.*permanent.*kulungan|hindi.*lagi.*kulungan'],
            'existing_pets' => ['pet|cat|dog|animal|alaga(?:ng)? hayop|pusa|aso', 'introduce|separate|gradual|ipakilala|hiwalay|dahan-dahan', 'spay|neuter|sterili|breed|kapon|dumami|pagpaparami', 'vet|clinic|guidance|beterinaryo|payo'],
        ];
        $matches = 0;
        foreach ($signals[$key] ?? [] as $signal) {
            if (preg_match('/' . str_replace('/', '\\/', $signal) . '/u', $answer)) ++$matches;
        }
        $indicatorCount = count($signals[$key] ?? []);
        if ($matches === $indicatorCount && $indicatorCount > 0) return 4;
        if ($matches >= max(2, (int) ceil($indicatorCount * 0.6))) return 3;
        return $matches === 1 ? 2 : 1;
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
            if (trim((string) ($assessment['response'] ?? '')) === '' || $assessment['rating'] === null || $assessment['rating'] === '') {
                $missing[] = $key;
                continue;
            }
            $score += $this->earnedPoints((int) $assessment['rating'], (float) ($assessment['weight'] ?? $criterion['weight']));
        }
        return ['score' => $missing ? null : round($score, 2), 'complete' => $missing === [], 'missing' => $missing];
    }
}

<?php

use App\Services\AdoptionAssessmentService;
use CodeIgniter\Test\CIUnitTestCase;

final class AdoptionAssessmentServiceTest extends CIUnitTestCase
{
    private AdoptionAssessmentService $service;
    protected function setUp(): void { parent::setUp(); $this->service = new AdoptionAssessmentService(); }
    public function testWeightsTotalOneHundred(): void { $this->assertSame(100.0, $this->service->weightTotal()); }
    public function testWeightedPointsAndThresholds(): void { $this->assertSame(11.25, $this->service->earnedPoints(3, 15.0)); $this->assertSame('Recommended for Approval Review', $this->service->category(80.0, true)); $this->assertSame('Needs Further Assessment', $this->service->category(60.0, true)); $this->assertSame('Not Yet Sufficiently Demonstrated', $this->service->category(59.99, true)); }
    public function testMinimumAndMaximumPossibleScores(): void { $maximum = []; $minimum = []; foreach ($this->service->criteria() as $key => $criterion) { $maximum[] = ['criterion_key' => $key, 'response' => 'A specific plan', 'rating' => 4, 'weight' => $criterion['weight']]; $minimum[] = ['criterion_key' => $key, 'response' => 'No assessable plan', 'rating' => 0, 'weight' => $criterion['weight']]; } $this->assertSame(100.0, $this->service->calculate($maximum)['score']); $this->assertSame(0.0, $this->service->calculate($minimum)['score']); }
    public function testMissingRatingsRemainProvisional(): void { $result = $this->service->calculate([['criterion_key' => 'food_water', 'response' => '', 'rating' => null, 'weight' => 8]]); $this->assertFalse($result['complete']); $this->assertNull($result['score']); $this->assertSame('Provisional — incomplete assessment', $this->service->category($result['score'], $result['complete'])); }
    public function testWelfareFlagsAreConservative(): void { $this->assertSame('Needs Clarification', $this->service->inferredWelfareStatus('We will keep the cat in a cage forever.', 'humane')); $this->assertSame('Needs Clarification', $this->service->inferredWelfareStatus('A carrier will only be used for vet visits.', 'humane')); $this->assertTrue($this->service->hasExistingPetConcern('Our current cat is not yet neutered.')); }
    public function testAutomaticRubricUsesCareIndicatorsNotEssayLength(): void { $this->assertSame(4, $this->service->automaticRating('food_water', 'We will provide daily cat food, clean water, and a feeding routine.')); $this->assertSame(1, $this->service->automaticRating('food_water', 'Yes.')); $this->assertSame(2, $this->service->automaticRating('indoor_living', 'The cat will be outdoor only.')); $this->assertSame(4, $this->service->automaticRating('indoor_living', 'The cat will live in a combined indoor and outdoor enclosure; we will remove toxic hazards, supervise it, use secure fencing and screens, microchip it to prevent it getting lost, and provide shelter, water, and resting areas.')); }
}

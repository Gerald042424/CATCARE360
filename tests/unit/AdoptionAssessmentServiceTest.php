<?php

use App\Services\AdoptionAssessmentService;
use CodeIgniter\Test\CIUnitTestCase;

final class AdoptionAssessmentServiceTest extends CIUnitTestCase
{
    private AdoptionAssessmentService $service;
    protected function setUp(): void { parent::setUp(); $this->service = new AdoptionAssessmentService(); }
    public function testWeightsTotalOneHundred(): void { $this->assertSame(100.0, $this->service->weightTotal()); }
    public function testWeightedPointsAndCompletionState(): void { $this->assertSame(12.0, $this->service->earnedPoints(80, 15.0)); $this->assertSame('Completed — awaiting administrator decision', $this->service->category(80.0, true)); }
    public function testMinimumAndMaximumPossibleScores(): void { $maximum = []; $minimum = []; foreach ($this->service->criteria() as $key => $criterion) { $maximum[] = ['criterion_key' => $key, 'response' => 'A specific plan', 'rating' => 100, 'weight' => $criterion['weight']]; $minimum[] = ['criterion_key' => $key, 'response' => 'No assessable plan', 'rating' => 0, 'weight' => $criterion['weight']]; } $this->assertSame(100.0, $this->service->calculate($maximum)['score']); $this->assertSame(0.0, $this->service->calculate($minimum)['score']); }
    public function testMissingRatingsRemainProvisional(): void { $result = $this->service->calculate([['criterion_key' => 'food_water', 'response' => '', 'rating' => null, 'weight' => 8]]); $this->assertFalse($result['complete']); $this->assertNull($result['score']); $this->assertSame('Provisional — incomplete assessment', $this->service->category($result['score'], $result['complete'])); }
    public function testWelfareFlagsAreConservative(): void { $this->assertSame('Needs Clarification', $this->service->inferredWelfareStatus('We will keep the cat in a cage forever.', 'humane')); $this->assertSame('Needs Clarification', $this->service->inferredWelfareStatus('A carrier will only be used for vet visits.', 'humane')); $this->assertTrue($this->service->hasExistingPetConcern('Our current cat is not yet neutered.')); }
    public function testInvalidScoreDoesNotProduceFinalCalculation(): void { $rows=[]; foreach ($this->service->criteria() as $key => $criterion) $rows[]=['criterion_key'=>$key,'response'=>'Plan','rating'=>101,'weight'=>$criterion['weight']]; $this->assertFalse($this->service->calculate($rows)['complete']); }
}

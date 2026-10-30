<?php

use App\Controllers\Adopter\PortalController;
use CodeIgniter\Test\CIUnitTestCase;

final class PortalApplicationGroupingTest extends CIUnitTestCase
{
    public function testCriterionRowsBecomeOneMainAndFourOrderedSubquestions(): void
    {
        $rows = [
            ['criterion_key' => 'long_term_commitment', 'response' => "Main answer: Main commitment answer\n\nSubquestion 3: Third answer"],
            ['criterion_key' => 'daily_care', 'response' => 'Subquestion 1: First answer'],
            ['criterion_key' => 'family_readiness', 'response' => "Subquestion 2: Second answer\n\nSubquestion 4: Fourth answer"],
            // A repeated main answer from another scoring criterion must not
            // create a duplicate card or replace the first saved answer.
            ['criterion_key' => 'humane_housing', 'response' => 'Main answer: Housing answer'],
        ];

        $method = new ReflectionMethod(PortalController::class, 'groupApplicationAnswers');
        $groups = $method->invoke(new PortalController(), $rows);

        $this->assertCount(2, $groups);
        $this->assertStringStartsWith('1.', $groups[0]['title']);
        $this->assertSame('Main commitment answer', $groups[0]['main_answer']);
        $this->assertCount(4, $groups[0]['subquestions']);
        $this->assertSame(
            ['First answer', 'Second answer', 'Third answer', 'Fourth answer'],
            array_column($groups[0]['subquestions'], 'answer'),
        );
    }
}

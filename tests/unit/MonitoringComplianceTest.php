<?php

use CodeIgniter\Test\CIUnitTestCase;

final class MonitoringComplianceTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('monitoring');
    }

    public function testVerifiedEarlySubmissionIsCreditedWhenDueDateArrives(): void
    {
        $snapshot = calculateMonitoringComplianceSnapshot(
            [['id' => 13, 'adoption_date' => '2026-10-27']],
            [[
                'adoption_id' => 13,
                'checkpoint' => '3-day',
                'status' => 'Verified',
                'gps_verified' => 1,
                'submission_date' => '2026-10-28 19:47:00',
            ]],
            '2026-10-31',
        );

        $this->assertSame(1, $snapshot['expected_count']);
        $this->assertSame(1, $snapshot['verified_expected_count']);
        $this->assertSame(100, $snapshot['score']);
        $this->assertSame(0, $snapshot['missed_count']);
        $this->assertSame('None', $snapshot['flag']);
        $this->assertSame('2026-11-17', $snapshot['next_check_in']);
    }

    public function testCheckpointCountsOnItsDueDate(): void
    {
        $snapshot = calculateMonitoringComplianceSnapshot(
            [['id' => 13, 'adoption_date' => '2026-10-27']],
            [],
            '2026-10-30',
        );

        $this->assertTrue($snapshot['has_started']);
        $this->assertSame(1, $snapshot['expected_count']);
        $this->assertSame(0, $snapshot['score']);
        $this->assertSame(1, $snapshot['missed_count']);
        $this->assertSame('WANTED', $snapshot['flag']);
        $this->assertSame('2026-10-30', $snapshot['next_check_in']);
    }

    public function testNoDueCheckpointIsOnTrackInsteadOfNonCompliant(): void
    {
        $snapshot = calculateMonitoringComplianceSnapshot(
            [['id' => 13, 'adoption_date' => '2026-10-27']],
            [],
            '2026-10-29',
        );

        $this->assertFalse($snapshot['has_started']);
        $this->assertSame(0, $snapshot['expected_count']);
        $this->assertSame(0, $snapshot['missed_count']);
        $this->assertSame('None', $snapshot['flag']);
        $this->assertSame('2026-10-30', $snapshot['next_check_in']);
    }
}

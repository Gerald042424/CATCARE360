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

    public function testEachAdoptionUsesOnlyItsOwnMonitoringSubmissions(): void
    {
        $lucky = calculateMonitoringComplianceSnapshot(
            [['id' => 13, 'adoption_date' => '2026-10-27']],
            [['adoption_id' => 13, 'checkpoint' => '3-day', 'status' => 'Verified', 'gps_verified' => 1]],
            '2026-11-02',
        );
        $kitsune = calculateMonitoringComplianceSnapshot(
            [['id' => 21, 'adoption_date' => '2026-11-02']],
            [],
            '2026-11-02',
        );

        $this->assertSame(100, $lucky['score']);
        $this->assertSame(1, $lucky['verified_expected_count']);
        $this->assertSame('2026-11-17', $lucky['next_check_in']);
        $this->assertFalse($kitsune['has_started']);
        $this->assertSame(0, $kitsune['verified_expected_count']);
        $this->assertSame('2026-11-05', $kitsune['next_check_in']);
    }

    public function testPerCatDashboardDoesNotUseTheCombinedAdopterScore(): void
    {
        $adoptions = [
            ['id' => 13, 'adoption_date' => '2026-10-27'],
            ['id' => 21, 'adoption_date' => '2026-10-27'],
        ];
        $submissions = [[
            'adoption_id' => 13,
            'checkpoint' => '3-day',
            'status' => 'Verified',
            'gps_verified' => 1,
        ]];

        $combined = calculateMonitoringComplianceSnapshot($adoptions, $submissions, '2026-10-31');
        $lucky = calculateMonitoringComplianceSnapshot([$adoptions[0]], $submissions, '2026-10-31');
        $kitsune = calculateMonitoringComplianceSnapshot([$adoptions[1]], [], '2026-10-31');

        $this->assertSame(50, $combined['score']);
        $this->assertSame(100, $lucky['score']);
        $this->assertSame(0, $kitsune['score']);
        $this->assertSame(1, $lucky['expected_count']);
        $this->assertSame(1, $kitsune['expected_count']);
    }
}

<?php

if (! function_exists('monitoringCheckpoints')) {
    function monitoringCheckpoints(?string $adoptionDate): array
    {
        if (! $adoptionDate) {
            return [];
        }

        return [
            '3-day' => date('Y-m-d', strtotime($adoptionDate . ' +3 day')),
            '3-week' => date('Y-m-d', strtotime($adoptionDate . ' +21 days')),
            '3-month' => date('Y-m-d', strtotime($adoptionDate . ' +90 days')),
        ];
    }
}

if (! function_exists('monitoringFlagFromMissed')) {
    function monitoringFlagFromMissed(int $missed): string
    {
        if ($missed >= 2) {
            return 'MISSING';
        }

        if ($missed === 1) {
            return 'WANTED';
        }

        return 'None';
    }
}

if (! function_exists('calculateMonitoringComplianceSnapshot')) {
    function calculateMonitoringComplianceSnapshot(array $adoptions, array $submissions, ?string $today = null): array
    {
        $today ??= date('Y-m-d');
        $expected = [];
        $allCheckpoints = [];
        foreach ($adoptions as $adoption) {
            foreach (monitoringCheckpoints($adoption['adoption_date']) as $checkpoint => $dueDate) {
                $key = $adoption['id'] . ':' . $checkpoint;
                $allCheckpoints[$key] = $dueDate;
                // A checkpoint is required on its due date. A verified update
                // submitted early is still credited when that date arrives.
                if ($dueDate <= $today) {
                    $expected[$key] = true;
                }
            }
        }

        $verified = [];
        $gpsVerified = 0;
        foreach ($submissions as $submission) {
            if (! empty($submission['gps_verified'])) {
                ++$gpsVerified;
            }
            if (strcasecmp((string) ($submission['status'] ?? ''), 'Verified') === 0) {
                $verified[$submission['adoption_id'] . ':' . $submission['checkpoint']] = true;
            }
        }

        $verifiedExpected = count(array_intersect_key($verified, $expected));
        $expectedCount = count($expected);
        $missed = max(0, $expectedCount - $verifiedExpected);
        $score = $expectedCount > 0 ? (int) round(($verifiedExpected / $expectedCount) * 100) : 0;
        $outstandingDates = array_diff_key($allCheckpoints, $verified);

        return [
            'score' => $score,
            'expected_count' => $expectedCount,
            'verified_expected_count' => $verifiedExpected,
            'missed_count' => $missed,
            'has_started' => $expectedCount > 0,
            'total_submissions' => count($submissions),
            'gps_verified_count' => $gpsVerified,
            'flag' => monitoringFlagFromMissed($missed),
            'next_check_in' => empty($outstandingDates) ? null : min($outstandingDates),
        ];
    }
}

if (! function_exists('recalculateAdopterMonitoringCompliance')) {
    function recalculateAdopterMonitoringCompliance(int $adopterId): array
    {
        $db = db_connect();
        $snapshot = adopterMonitoringComplianceSnapshot($adopterId);

        $db->table('adopters')->where('id', $adopterId)->update([
            'compliance_score' => $snapshot['score'],
            'total_submissions' => $snapshot['total_submissions'],
            'gps_verified_count' => $snapshot['gps_verified_count'],
            'overdue_visits' => $snapshot['missed_count'],
            'flag' => $snapshot['flag'],
            'next_check_in' => $snapshot['next_check_in'],
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $snapshot;
    }
}

if (! function_exists('adopterMonitoringComplianceSnapshot')) {
    function adopterMonitoringComplianceSnapshot(int $adopterId): array
    {
        $db = db_connect();
        $adoptions = $db->table('adoptions')->where(['adopter_id' => $adopterId, 'status' => 'Active'])->get()->getResultArray();
        $submissions = $db->table('monitoring_submissions')->where('adopter_id', $adopterId)->get()->getResultArray();

        return calculateMonitoringComplianceSnapshot($adoptions, $submissions);
    }
}

if (! function_exists('activeAdopterMonitoringComplianceSnapshots')) {
    function activeAdopterMonitoringComplianceSnapshots(): array
    {
        $activeAdopters = db_connect()->table('adoptions')
            ->select('adopter_id')
            ->where('status', 'Active')
            ->distinct()
            ->get()
            ->getResultArray();
        $snapshots = [];
        foreach ($activeAdopters as $row) {
            $adopterId = (int) $row['adopter_id'];
            $snapshots[$adopterId] = adopterMonitoringComplianceSnapshot($adopterId);
        }

        return $snapshots;
    }
}

if (! function_exists('refreshActiveAdopterMonitoringCompliance')) {
    function refreshActiveAdopterMonitoringCompliance(): array
    {
        $snapshots = [];
        foreach (array_keys(activeAdopterMonitoringComplianceSnapshots()) as $adopterId) {
            $snapshots[$adopterId] = recalculateAdopterMonitoringCompliance($adopterId);
        }

        return $snapshots;
    }
}

if (! function_exists('closeMonitoringCaseWhenComplete')) {
    function closeMonitoringCaseWhenComplete(int $adoptionId): bool
    {
        $db = db_connect();
        $adoption = $db->table('adoptions')->where('id', $adoptionId)->get()->getRowArray();
        if (! $adoption || ($adoption['status'] ?? '') !== 'Active') return false;
        $verified = $db->table('monitoring_submissions')->where('adoption_id', $adoptionId)->where('status', 'Verified')->select('checkpoint')->get()->getResultArray();
        $completed = array_column($verified, 'checkpoint');
        foreach (['3-day', '3-week', '3-month'] as $checkpoint) if (! in_array($checkpoint, $completed, true)) return false;
        $db->table('adoptions')->where('id', $adoptionId)->update(['status' => 'Completed', 'monitoring_closed_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        $exists = $db->table('animal_journey_events')->where(['animal_id' => $adoption['animal_id'], 'event_type' => 'Monitoring Closed'])->countAllResults();
        if (! $exists) $db->table('animal_journey_events')->insert(['animal_id' => $adoption['animal_id'], 'event_type' => 'Monitoring Closed', 'title' => '3-3-3 monitoring case closed', 'description' => 'All required post-adoption updates were verified. The monitoring case is archived for reporting.', 'event_date' => date('Y-m-d'), 'created_at' => date('Y-m-d H:i:s')]);
        return true;
    }
}

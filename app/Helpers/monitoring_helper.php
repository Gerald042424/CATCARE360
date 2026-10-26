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

if (! function_exists('recalculateAdopterMonitoringCompliance')) {
    function recalculateAdopterMonitoringCompliance(int $adopterId): void
    {
        $db = db_connect();
        $adoptions = $db->table('adoptions')->where(['adopter_id' => $adopterId, 'status' => 'Active'])->get()->getResultArray();
        $expected = [];
        $nextDue = [];
        foreach ($adoptions as $adoption) {
            foreach (monitoringCheckpoints($adoption['adoption_date']) as $checkpoint => $dueDate) {
                // A checkpoint becomes overdue only after its due date has passed.
                // The adopter keeps the full due date to submit the required update.
                if ($dueDate < date('Y-m-d')) $expected[$adoption['id'] . ':' . $checkpoint] = true;
                else $nextDue[] = $dueDate;
            }
        }

        $submissions = $db->table('monitoring_submissions')->where('adopter_id', $adopterId)->get()->getResultArray();
        $verified = [];
        $gpsVerified = 0;
        foreach ($submissions as $submission) {
            if (! empty($submission['gps_verified'])) ++$gpsVerified;
            if (($submission['status'] ?? '') === 'Verified') $verified[$submission['adoption_id'] . ':' . $submission['checkpoint']] = true;
        }
        $verifiedExpected = count(array_intersect_key($verified, $expected));
        $expectedCount = count($expected);
        $missed = max(0, $expectedCount - $verifiedExpected);
        $score = $expectedCount > 0 ? (int) round(($verifiedExpected / $expectedCount) * 100) : 100;

        $db->table('adopters')->where('id', $adopterId)->update([
            'compliance_score' => $score,
            'total_submissions' => count($submissions),
            'gps_verified_count' => $gpsVerified,
            'overdue_visits' => $missed,
            'flag' => monitoringFlagFromMissed($missed),
            'next_check_in' => empty($nextDue) ? null : min($nextDue),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
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

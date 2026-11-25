<?php

namespace App\Models;

use CodeIgniter\Model;

class MonitoringSubmissionModel extends Model
{
    public const RESUBMISSION_REQUESTED = 'Resubmission Requested';

    protected $table = 'monitoring_submissions';
    protected $allowedFields = ['adopter_id', 'adoption_id', 'checkpoint', 'submission_date', 'photo_url', 'health_status', 'behavior_notes', 'gps_lat', 'gps_lng', 'gps_accuracy', 'gps_verified', 'capture_source', 'captured_at', 'device_info', 'user_agent', 'ip_address', 'status'];
    protected $useTimestamps = true;

    public function nextCheckIn(array $submissions): ?array
    {
        $byCheckpoint = [];
        foreach ($submissions as $submission) {
            $checkpoint = $submission['checkpoint'];
            if (! isset($byCheckpoint[$checkpoint]) || (int) $submission['id'] > (int) $byCheckpoint[$checkpoint]['id']) {
                $byCheckpoint[$checkpoint] = $submission;
            }
        }
        $checkpoints = ['3-day', '3-week', '3-month'];
        foreach ($checkpoints as $checkpoint) {
            $submission = $byCheckpoint[$checkpoint] ?? null;
            if (($submission['status'] ?? null) === self::RESUBMISSION_REQUESTED) {
                return ['checkpoint' => $checkpoint, 'replacement' => $submission];
            }
        }
        foreach ($checkpoints as $checkpoint) {
            if (! isset($byCheckpoint[$checkpoint])) {
                return ['checkpoint' => $checkpoint, 'replacement' => null];
            }
        }
        return null;
    }

    public function updateWithStatus(int $id, string $expectedStatus, array $changes): bool
    {
        return $this->where('status', $expectedStatus)->update($id, $changes)
            && $this->db->affectedRows() === 1;
    }
}

<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

class AdoptionReturnService
{
    public const REASONS = ['Financial difficulty', 'Housing or relocation', 'Health or family circumstances', 'Other'];
    public const OPEN_STATUSES = ['Submitted', 'Handover Scheduled'];
    public const CUSTODY_STATUSES = ['Active', 'Completed'];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    public function submit(int $adoptionId, int $adopterId, string $reason, string $notes, bool $urgent): bool
    {
        if (! in_array($reason, self::REASONS, true) || mb_strlen($notes) > 1000
            || ! $this->db->tableExists('adoption_return_requests')) {
            return false;
        }
        if (! $this->db->table('adoptions')->where([
            'id' => $adoptionId, 'adopter_id' => $adopterId,
        ])->whereIn('status', self::CUSTODY_STATUSES)->countAllResults()) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $data = [
            'reason' => $reason, 'notes' => $notes, 'urgent' => (int) $urgent,
            'status' => 'Submitted', 'handover_at' => null, 'handover_location' => null,
            'created_at' => $now, 'updated_at' => $now,
        ];
        $requests = $this->db->table('adoption_return_requests');
        if ($requests->where('adoption_id', $adoptionId)->countAllResults() > 0) {
            return $this->updateRequest($adoptionId, ['Cancelled'], $data);
        }

        $requests->ignore(true)->insert(['adoption_id' => $adoptionId] + $data);
        return $this->db->affectedRows() === 1;
    }

    public function schedule(int $adoptionId, string $handoverAt, string $location): bool
    {
        $timezone = new \DateTimeZone('Asia/Manila');
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $handoverAt, $timezone);
        if (! $date || $date->format('Y-m-d\TH:i') !== $handoverAt
            || $date <= new \DateTimeImmutable('now', $timezone)
            || $location === '' || mb_strlen($location) > 255
            || ! $this->db->tableExists('adoption_return_requests')) {
            return false;
        }
        return $this->updateRequest($adoptionId, self::OPEN_STATUSES, [
            'status' => 'Handover Scheduled', 'handover_at' => $date->format('Y-m-d H:i:s'),
            'handover_location' => $location,
        ]);
    }

    public function cancel(int $adoptionId, ?int $adopterId = null): bool
    {
        if (! $this->db->tableExists('adoption_return_requests')) {
            return false;
        }
        if ($adopterId !== null && ! $this->db->table('adoptions')->where([
            'id' => $adoptionId, 'adopter_id' => $adopterId,
        ])->countAllResults()) {
            return false;
        }
        return $this->updateRequest($adoptionId, self::OPEN_STATUSES, ['status' => 'Cancelled']);
    }

    public function confirm(int $adoptionId): bool
    {
        if (! $this->db->tableExists('adoption_return_requests')) {
            return false;
        }
        $adoption = $this->db->table('adoptions')->where('id', $adoptionId)
            ->whereIn('status', self::CUSTODY_STATUSES)->get()->getRowArray();
        if (! $adoption || ! $adoption['animal_id']) {
            return false;
        }

        if (! $this->db->transBegin()) {
            return false;
        }
        $committed = false;
        try {
            if (! $this->updateRequest($adoptionId, ['Handover Scheduled'], ['status' => 'Returned'])) {
                return false;
            }
            $this->db->table('adoptions')->where('id', $adoptionId)->whereIn('status', self::CUSTODY_STATUSES)
                ->update(['status' => 'Returned', 'updated_at' => date('Y-m-d H:i:s')]);
            if ($this->db->affectedRows() !== 1) {
                return false;
            }
            $this->db->table('animals')->where(['id' => $adoption['animal_id'], 'status' => 'Adopted'])
                ->update(['status' => 'Under Assessment', 'updated_at' => date('Y-m-d H:i:s')]);
            if ($this->db->affectedRows() !== 1) {
                return false;
            }
            $this->db->table('animal_journey_events')->insert([
                'animal_id' => $adoption['animal_id'],
                'event_type' => 'Adoption',
                'title' => 'Returned to McKinley West Cats',
                'description' => 'Staff received the cat and placed it under assessment.',
                'event_date' => date('Y-m-d'),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $committed = $this->db->transStatus() && $this->db->transCommit();
            return $committed;
        } finally {
            if (! $committed) {
                $this->db->transRollback();
                $this->db->resetTransStatus();
            }
        }
    }

    private function updateRequest(int $adoptionId, array $allowedStatuses, array $data): bool
    {
        $this->db->table('adoption_return_requests')->where('adoption_id', $adoptionId)
            ->whereIn('status', $allowedStatuses)->update($data + ['updated_at' => date('Y-m-d H:i:s')]);
        return $this->db->affectedRows() === 1;
    }
}

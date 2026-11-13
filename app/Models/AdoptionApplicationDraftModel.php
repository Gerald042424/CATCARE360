<?php

namespace App\Models;

use CodeIgniter\Model;

class AdoptionApplicationDraftModel extends Model
{
    protected $table = 'adoption_application_drafts_by_cat';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['adopter_id', 'context_cat_id', 'payload', 'current_step', 'created_at', 'updated_at'];
    protected $useTimestamps = false;

    /** Keep Vercel deployments usable even when migrations are not run as a build step. */
    public function ensureTable(): void
    {
        if ($this->db->tableExists($this->table)) {
            return;
        }

        $this->db->query(
            'CREATE TABLE IF NOT EXISTS `adoption_application_drafts_by_cat` ('
            . '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . '`adopter_id` INT UNSIGNED NOT NULL,'
            . '`context_cat_id` INT UNSIGNED NOT NULL DEFAULT 0,'
            . '`payload` MEDIUMTEXT NOT NULL,'
            . '`current_step` TINYINT UNSIGNED NOT NULL DEFAULT 0,'
            . '`created_at` DATETIME NULL,'
            . '`updated_at` DATETIME NULL,'
            . 'PRIMARY KEY (`id`), UNIQUE KEY `application_draft_adopter_cat` (`adopter_id`, `context_cat_id`)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function findForAdopter(int $adopterId, int $contextCatId): ?array
    {
        $this->ensureTable();
        $this->migrateLegacyDraft($adopterId, $contextCatId);
        return $this->where('adopter_id', $adopterId)->where('context_cat_id', $contextCatId)->first();
    }

    public function saveForAdopter(int $adopterId, int $contextCatId, array $payload, int $currentStep): void
    {
        $this->ensureTable();
        $now = date('Y-m-d H:i:s');
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $this->db->query(
            'INSERT INTO `adoption_application_drafts_by_cat` (`adopter_id`, `context_cat_id`, `payload`, `current_step`, `created_at`, `updated_at`) '
            . 'VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `payload` = VALUES(`payload`), '
            . '`current_step` = VALUES(`current_step`), `updated_at` = VALUES(`updated_at`)',
            [$adopterId, $contextCatId, $json, $currentStep, $now, $now]
        );
    }

    public function deleteForAdopter(int $adopterId, int $contextCatId): void
    {
        $this->ensureTable();
        $this->where('adopter_id', $adopterId)->where('context_cat_id', $contextCatId)->delete();
    }

    /** Move the one-per-adopter draft created by the first release into its selected cat context. */
    private function migrateLegacyDraft(int $adopterId, int $requestedContextCatId): void
    {
        if (! $this->db->tableExists('adoption_application_drafts')) {
            return;
        }
        $legacy = $this->db->table('adoption_application_drafts')->where('adopter_id', $adopterId)->get()->getRowArray();
        if (! $legacy) {
            return;
        }
        $payload = json_decode((string) ($legacy['payload'] ?? ''), true);
        $savedCatIds = is_array($payload) ? array_values(array_filter(array_map('intval', (array) ($payload['cat_ids'] ?? [])))) : [];
        $contextCatId = $savedCatIds[0] ?? max(0, $requestedContextCatId);
        $existing = $this->where('adopter_id', $adopterId)->where('context_cat_id', $contextCatId)->first();
        if (! $existing) {
            $now = date('Y-m-d H:i:s');
            $this->db->table($this->table)->insert([
                'adopter_id' => $adopterId,
                'context_cat_id' => $contextCatId,
                'payload' => (string) $legacy['payload'],
                'current_step' => (int) ($legacy['current_step'] ?? 0),
                'created_at' => $legacy['created_at'] ?? $now,
                'updated_at' => $legacy['updated_at'] ?? $now,
            ]);
        }
        $this->db->table('adoption_application_drafts')->where('adopter_id', $adopterId)->delete();
    }
}

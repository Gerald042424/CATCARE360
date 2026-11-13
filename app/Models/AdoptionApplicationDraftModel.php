<?php

namespace App\Models;

use CodeIgniter\Model;

class AdoptionApplicationDraftModel extends Model
{
    protected $table = 'adoption_application_drafts';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['adopter_id', 'payload', 'current_step', 'created_at', 'updated_at'];
    protected $useTimestamps = false;

    /** Keep Vercel deployments usable even when migrations are not run as a build step. */
    public function ensureTable(): void
    {
        if ($this->db->tableExists($this->table)) {
            return;
        }

        $this->db->query(
            'CREATE TABLE IF NOT EXISTS `adoption_application_drafts` ('
            . '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . '`adopter_id` INT UNSIGNED NOT NULL,'
            . '`payload` MEDIUMTEXT NOT NULL,'
            . '`current_step` TINYINT UNSIGNED NOT NULL DEFAULT 0,'
            . '`created_at` DATETIME NULL,'
            . '`updated_at` DATETIME NULL,'
            . 'PRIMARY KEY (`id`), UNIQUE KEY `adoption_application_drafts_adopter_id` (`adopter_id`)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function findForAdopter(int $adopterId): ?array
    {
        $this->ensureTable();
        return $this->where('adopter_id', $adopterId)->first();
    }

    public function saveForAdopter(int $adopterId, array $payload, int $currentStep): void
    {
        $this->ensureTable();
        $now = date('Y-m-d H:i:s');
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $this->db->query(
            'INSERT INTO `adoption_application_drafts` (`adopter_id`, `payload`, `current_step`, `created_at`, `updated_at`) '
            . 'VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `payload` = VALUES(`payload`), '
            . '`current_step` = VALUES(`current_step`), `updated_at` = VALUES(`updated_at`)',
            [$adopterId, $json, $currentStep, $now, $now]
        );
    }

    public function deleteForAdopter(int $adopterId): void
    {
        $this->ensureTable();
        $this->where('adopter_id', $adopterId)->delete();
    }
}

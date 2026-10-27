<?php

namespace App\Database\Migrations;

use App\Database\Seeds\CatCareSeeder;
use CodeIgniter\Database\Migration;

class SeedDemoDataForEmptyDatabase extends Migration
{
    public function up(): void
    {
        // New hosted databases receive demo data once only. Existing records
        // (including local/client data) are deliberately left untouched.
        if ((int) $this->db->table('animals')->countAllResults() === 0) {
            (new CatCareSeeder($this->db))->run();
        }
    }

    public function down(): void
    {
        // Seeded records are intentionally retained on a schema rollback.
    }
}

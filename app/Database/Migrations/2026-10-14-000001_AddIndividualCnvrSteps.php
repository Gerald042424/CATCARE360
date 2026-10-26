<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIndividualCnvrSteps extends Migration
{
    public function up()
    {
        $this->forge->addColumn('animals', [
            'cnvr_catch' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'cnvr_status'],
            'cnvr_neuter' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'cnvr_catch'],
            'cnvr_vaccinate' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'cnvr_neuter'],
            'cnvr_return' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'cnvr_vaccinate'],
        ]);

        // Preserve the meaning of the old aggregate status for already completed records.
        $this->db->table('animals')->where('cnvr_status', 'CNVR - Completed')->update([
            'cnvr_catch' => 1,
            'cnvr_neuter' => 1,
            'cnvr_vaccinate' => 1,
            'cnvr_return' => 1,
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('animals', ['cnvr_catch', 'cnvr_neuter', 'cnvr_vaccinate', 'cnvr_return']);
    }
}

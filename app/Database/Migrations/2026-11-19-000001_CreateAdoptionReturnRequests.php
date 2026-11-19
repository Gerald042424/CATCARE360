<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAdoptionReturnRequests extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE animals MODIFY status ENUM('Available', 'Adopted', 'Medical', 'Foster', 'Under Assessment') NOT NULL DEFAULT 'Available'");
        $this->forge->addField([
            'adoption_id' => ['type' => 'INT', 'unsigned' => true],
            'reason' => ['type' => 'VARCHAR', 'constraint' => 60],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'urgent' => ['type' => 'TINYINT', 'default' => 0],
            'status' => ['type' => 'VARCHAR', 'constraint' => 24],
            'handover_at' => ['type' => 'DATETIME', 'null' => true],
            'handover_location' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('adoption_id', true);
        $this->forge->addForeignKey('adoption_id', 'adoptions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('adoption_return_requests', true);
    }

    public function down()
    {
        $this->forge->dropTable('adoption_return_requests', true);
        // Retain the additive status value and any cats held for assessment.
    }
}

<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAdoptionApplicationDrafts extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'adopter_id' => ['type' => 'INT', 'unsigned' => true],
            'payload' => ['type' => 'MEDIUMTEXT'],
            'current_step' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('adopter_id');
        $this->forge->createTable('adoption_application_drafts', true);
    }

    public function down()
    {
        $this->forge->dropTable('adoption_application_drafts', true);
    }
}

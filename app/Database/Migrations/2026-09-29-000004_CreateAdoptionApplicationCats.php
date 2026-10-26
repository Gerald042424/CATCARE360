<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAdoptionApplicationCats extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'application_id' => ['type' => 'INT', 'unsigned' => true],
            'cat_id' => ['type' => 'INT', 'unsigned' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['Under Review', 'Approved', 'Rejected'], 'default' => 'Under Review'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['application_id', 'cat_id']);
        $this->forge->addForeignKey('application_id', 'adoption_applications', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('cat_id', 'animals', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('adoption_application_cats', true);
    }

    public function down()
    {
        $this->forge->dropTable('adoption_application_cats', true);
    }
}

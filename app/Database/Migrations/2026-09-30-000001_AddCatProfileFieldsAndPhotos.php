<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCatProfileFieldsAndPhotos extends Migration
{
    public function up()
    {
        $this->forge->addColumn('animals', [
            'color' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'weight'],
            'cnvr_status' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'health'],
            'location' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'cnvr_status'],
        ]);
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'animal_id' => ['type' => 'INT', 'unsigned' => true],
            'photo_url' => ['type' => 'VARCHAR', 'constraint' => 255],
            'caption' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('animal_id', 'animals', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('animal_photos', true);
    }

    public function down()
    {
        $this->forge->dropTable('animal_photos', true);
        $this->forge->dropColumn('animals', ['color', 'cnvr_status', 'location']);
    }
}

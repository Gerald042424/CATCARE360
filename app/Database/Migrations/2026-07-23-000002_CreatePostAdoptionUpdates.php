<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePostAdoptionUpdates extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'adopter_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'cat_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'update_type' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'photo_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'latitude' => ['type' => 'DECIMAL', 'constraint' => '10,8', 'null' => true],
            'longitude' => ['type' => 'DECIMAL', 'constraint' => '11,8', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['Pending', 'Verified', 'Flagged'], 'default' => 'Pending'],
            'submitted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('adopter_id', 'adopters', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('cat_id', 'animals', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('post_adoption_updates', true);
    }

    public function down()
    {
        $this->forge->dropTable('post_adoption_updates', true);
    }
}

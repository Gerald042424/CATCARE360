<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAdoptionApplications extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'application_id' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'adopter_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'cat_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'cat_description' => ['type' => 'TEXT', 'null' => true],
            'lifetime_commitment' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'secured_space' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'food_water' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'vet_bills' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'spay_neuter' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'deworming' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'tick_flea' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'family_comfortable' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'love_attention' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'caretaker_when_away' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'selfie_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'gov_id_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'address_proof_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'home_photos_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['Under Review', 'Approved', 'Rejected'], 'default' => 'Under Review'],
            'submitted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('application_id');
        $this->forge->addForeignKey('adopter_id', 'adopters', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('cat_id', 'animals', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('adoption_applications', true);
    }

    public function down()
    {
        $this->forge->dropTable('adoption_applications', true);
    }
}

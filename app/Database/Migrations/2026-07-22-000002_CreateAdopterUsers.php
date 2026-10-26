<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAdopterUsers extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'adopter_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 150],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'first_name' => ['type' => 'VARCHAR', 'constraint' => 80],
            'last_name' => ['type' => 'VARCHAR', 'constraint' => 80],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->addForeignKey('adopter_id', 'adopters', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('adopter_users', true);
    }

    public function down()
    {
        $this->forge->dropTable('adopter_users', true);
    }
}

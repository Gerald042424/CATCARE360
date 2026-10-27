<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStaff extends Migration
{
    public function up()
    {
        $staffPassword = (string) env('demo.staff.password', '');
        if ($staffPassword === '') {
            throw new \RuntimeException('Set demo.staff.password before creating the hosted demo database.');
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'email' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'role' => ['type' => 'ENUM', 'constraint' => ['admin', 'staff'], 'default' => 'staff'],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('staff', true);

        $this->db->table('staff')->ignore(true)->insert([
            'name' => 'Admin',
            'email' => 'staff@catcare360.org',
            'password_hash' => password_hash($staffPassword, PASSWORD_DEFAULT),
            'role' => 'admin',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('staff', true);
    }
}

<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ReplaceVolunteerAccessCodesWithPasswords extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('volunteer_users') || ! $this->db->fieldExists('access_code', 'volunteer_users')) return;

        $users = $this->db->table('volunteer_users')->select('id, access_code, password_hash')->get()->getResultArray();
        foreach ($users as $user) {
            if (empty($user['password_hash']) && ! empty($user['access_code'])) {
                $this->db->table('volunteer_users')->where('id', $user['id'])->update(['password_hash' => password_hash($user['access_code'], PASSWORD_DEFAULT)]);
            }
        }

        $this->forge->dropColumn('volunteer_users', 'access_code');
    }

    public function down()
    {
        if ($this->db->tableExists('volunteer_users') && ! $this->db->fieldExists('access_code', 'volunteer_users')) {
            $this->forge->addColumn('volunteer_users', ['access_code' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true]]);
        }
    }
}

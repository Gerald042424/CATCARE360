<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApplicationPersonalDetails extends Migration
{
    public function up()
    {
        $this->forge->addColumn('adoption_applications', [
            'full_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'cat_description'],
            'address' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'full_name'],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'address'],
            'occupation' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'phone'],
            'housing_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'occupation'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('adoption_applications', ['full_name', 'address', 'phone', 'occupation', 'housing_type']);
    }
}

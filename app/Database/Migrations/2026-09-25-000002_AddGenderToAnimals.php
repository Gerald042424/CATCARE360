<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGenderToAnimals extends Migration
{
    public function up()
    {
        $this->forge->addColumn('animals', [
            'gender' => [
                'type' => 'ENUM',
                'constraint' => ['Male', 'Female', 'Unknown'],
                'default' => 'Unknown',
                'after' => 'weight',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('animals', 'gender');
    }
}

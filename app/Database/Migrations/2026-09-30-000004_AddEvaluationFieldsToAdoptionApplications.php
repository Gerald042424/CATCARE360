<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEvaluationFieldsToAdoptionApplications extends Migration
{
    public function up()
    {
        $this->forge->addColumn('adoption_applications', [
            'pet_ownership_experience' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'after' => 'occupation'],
            'monthly_pet_budget' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'after' => 'pet_ownership_experience'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('adoption_applications', ['pet_ownership_experience', 'monthly_pet_budget']);
    }
}

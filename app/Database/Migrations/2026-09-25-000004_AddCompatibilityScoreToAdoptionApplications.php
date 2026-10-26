<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCompatibilityScoreToAdoptionApplications extends Migration
{
    public function up()
    {
        $this->forge->addColumn('adoption_applications', [
            'compatibility_score' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0, 'after' => 'housing_type'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('adoption_applications', 'compatibility_score');
    }
}

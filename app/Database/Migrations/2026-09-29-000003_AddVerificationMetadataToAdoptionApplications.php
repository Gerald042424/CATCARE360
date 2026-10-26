<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVerificationMetadataToAdoptionApplications extends Migration
{
    public function up()
    {
        $this->forge->addColumn('adoption_applications', [
            'verification_metadata' => ['type' => 'TEXT', 'null' => true, 'after' => 'home_photos_path'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('adoption_applications', 'verification_metadata');
    }
}

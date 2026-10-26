<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOcrScreeningToAdoptionApplications extends Migration
{
    public function up()
    {
        $this->forge->addColumn('adoption_applications', [
            'ocr_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'verification_metadata'],
            'ocr_message' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'after' => 'ocr_status'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('adoption_applications', ['ocr_status', 'ocr_message']);
    }
}

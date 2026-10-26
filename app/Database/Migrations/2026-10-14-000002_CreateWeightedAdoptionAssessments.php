<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWeightedAdoptionAssessments extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'application_id' => ['type' => 'INT', 'unsigned' => true],
            'criterion_key' => ['type' => 'VARCHAR', 'constraint' => 80],
            'response' => ['type' => 'TEXT', 'null' => true],
            'weight' => ['type' => 'DECIMAL', 'constraint' => '5,2'],
            'rating' => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'rating_reason' => ['type' => 'TEXT', 'null' => true],
            'earned_points' => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['application_id', 'criterion_key']);
        $this->forge->addForeignKey('application_id', 'adoption_applications', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('adoption_application_assessments', true);
        $this->forge->addColumn('adoption_applications', [
            'assessment_score' => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true, 'after' => 'compatibility_score'],
            'assessment_status' => ['type' => 'VARCHAR', 'constraint' => 80, 'default' => 'Provisional — incomplete assessment', 'after' => 'assessment_score'],
            'indoor_living_compliance' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'Pending Staff Review', 'after' => 'assessment_status'],
            'humane_housing_compliance' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'Pending Staff Review', 'after' => 'indoor_living_compliance'],
            'existing_pet_concern' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'humane_housing_compliance'],
            'staff_notes' => ['type' => 'TEXT', 'null' => true, 'after' => 'existing_pet_concern'],
            'final_decision_reason' => ['type' => 'TEXT', 'null' => true, 'after' => 'staff_notes'],
            'assessment_updated_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'final_decision_reason'],
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('adoption_application_assessments', true);
        $this->forge->dropColumn('adoption_applications', ['assessment_score', 'assessment_status', 'indoor_living_compliance', 'humane_housing_compliance', 'existing_pet_concern', 'staff_notes', 'final_decision_reason', 'assessment_updated_at']);
    }
}

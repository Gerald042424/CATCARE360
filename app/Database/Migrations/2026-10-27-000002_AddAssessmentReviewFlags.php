<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAssessmentReviewFlags extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'application_id' => ['type' => 'INT', 'unsigned' => true],
            'question_group' => ['type' => 'VARCHAR', 'constraint' => 40],
            'status' => ['type' => 'VARCHAR', 'constraint' => 40],
            'reason' => ['type' => 'TEXT'],
            'criterion_key' => ['type' => 'VARCHAR', 'constraint' => 80],
            'related_answers' => ['type' => 'TEXT', 'null' => true],
            'review_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'Open'],
            'review_notes' => ['type' => 'TEXT', 'null' => true],
            'reviewed_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'reviewed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addKey(['application_id', 'review_status']);
        $this->forge->addForeignKey('application_id', 'adoption_applications', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('adoption_assessment_flags', true);
    }
    public function down() { $this->forge->dropTable('adoption_assessment_flags', true); }
}

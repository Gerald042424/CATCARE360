<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCatCareTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'VARCHAR', 'constraint' => 128],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45],
            'timestamp' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'data' => ['type' => 'BLOB'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('timestamp');
        $this->forge->createTable('ci_sessions', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'species' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'Cat'],
            'breed' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'age' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'weight' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['Available', 'Adopted', 'Medical', 'Foster'], 'default' => 'Available'],
            'stage' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'health' => ['type' => 'ENUM', 'constraint' => ['Healthy', 'Recovering', 'Under Treatment', 'Critical'], 'default' => 'Healthy'],
            'intake_date' => ['type' => 'DATE', 'null' => true],
            'behavior_notes' => ['type' => 'TEXT', 'null' => true],
            'medical_notes' => ['type' => 'TEXT', 'null' => true],
            'image_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('animals', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'occupation' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'housing_type' => ['type' => 'ENUM', 'constraint' => ['House', 'Apartment', 'Condo', 'Rented', 'Owned'], 'null' => true],
            'address' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'address_lat' => ['type' => 'DECIMAL', 'constraint' => '10,8', 'null' => true],
            'address_lng' => ['type' => 'DECIMAL', 'constraint' => '11,8', 'null' => true],
            'compliance_score' => ['type' => 'INT', 'default' => 0],
            'next_check_in' => ['type' => 'DATE', 'null' => true],
            'total_submissions' => ['type' => 'INT', 'default' => 0],
            'overdue_visits' => ['type' => 'INT', 'default' => 0],
            'gps_verified_count' => ['type' => 'INT', 'default' => 0],
            'flag' => ['type' => 'ENUM', 'constraint' => ['None', 'WANTED', 'MISSING'], 'default' => 'None'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('adopters', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'adopter_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'animal_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'adoption_date' => ['type' => 'DATE', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['Active', 'Returned', 'Completed'], 'default' => 'Active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('adopter_id', 'adopters', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('animal_id', 'animals', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('adoptions', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'adopter_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'adoption_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'checkpoint' => ['type' => 'ENUM', 'constraint' => ['3-day', '3-week', '3-month'], 'null' => true],
            'submission_date' => ['type' => 'DATETIME', 'null' => true],
            'photo_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'health_status' => ['type' => 'TEXT', 'null' => true],
            'behavior_notes' => ['type' => 'TEXT', 'null' => true],
            'gps_lat' => ['type' => 'DECIMAL', 'constraint' => '10,8', 'null' => true],
            'gps_lng' => ['type' => 'DECIMAL', 'constraint' => '11,8', 'null' => true],
            'gps_verified' => ['type' => 'BOOLEAN', 'default' => false],
            'status' => ['type' => 'ENUM', 'constraint' => ['Verified', 'Pending', 'Flagged'], 'default' => 'Pending'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('adopter_id', 'adopters', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('adoption_id', 'adoptions', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('monitoring_submissions', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'applicant_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'occupation' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'housing_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'animal_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['Pending Review', 'Under Review', 'Approved', 'Rejected'], 'default' => 'Pending Review'],
            'compatibility_score' => ['type' => 'INT', 'default' => 0],
            'submitted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('animal_id', 'animals', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('applications', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'application_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'question' => ['type' => 'TEXT', 'null' => true],
            'answer' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('application_id', 'applications', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('application_answers', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'application_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'doc_type' => ['type' => 'ENUM', 'constraint' => ['gov_id', 'address_proof', 'home_photos', 'selfie_with_id'], 'null' => true],
            'file_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['Pending', 'Verified', 'Rejected'], 'default' => 'Pending'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('application_id', 'applications', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('application_documents', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'role' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'location' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['Active', 'Busy', 'Inactive'], 'default' => 'Active'],
            'availability' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'image_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'joined_date' => ['type' => 'DATE', 'null' => true],
            'tasks_completed' => ['type' => 'INT', 'default' => 0],
            'pending_tasks' => ['type' => 'INT', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('volunteers', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'category' => ['type' => 'ENUM', 'constraint' => ['Feeding', 'Cleaning', 'Medical', 'Transport', 'Socialization', 'Walking', 'Adoption'], 'null' => true],
            'priority' => ['type' => 'ENUM', 'constraint' => ['Low', 'Medium', 'High'], 'default' => 'Medium'],
            'status' => ['type' => 'ENUM', 'constraint' => ['Pending', 'In Progress', 'Completed'], 'default' => 'Pending'],
            'assigned_to' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'animal_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'task_date' => ['type' => 'DATE', 'null' => true],
            'task_time' => ['type' => 'TIME', 'null' => true],
            'location' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('assigned_to', 'volunteers', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('animal_id', 'animals', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('tasks', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'username' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'role' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'admin'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('username');
        $this->forge->createTable('admin_users', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'volunteer_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'access_code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->addForeignKey('volunteer_id', 'volunteers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('volunteer_users', true);
    }

    public function down()
    {
        foreach ([
            'volunteer_users',
            'admin_users',
            'tasks',
            'volunteers',
            'application_documents',
            'application_answers',
            'applications',
            'monitoring_submissions',
            'adoptions',
            'adopters',
            'animals',
            'ci_sessions',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}

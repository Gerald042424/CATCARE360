<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMonitoringClosureAndHomeVisits extends Migration
{
    public function up()
    {
        $this->forge->addColumn('adoptions', [
            'monitoring_closed_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'status'],
        ]);
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'adopter_id' => ['type' => 'INT', 'unsigned' => true],
            'adoption_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'scheduled_date' => ['type' => 'DATE', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['Scheduled', 'Completed', 'Cancelled'], 'default' => 'Scheduled'],
            'findings' => ['type' => 'TEXT', 'null' => true],
            'outcome' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['adopter_id', 'status']);
        $this->forge->addForeignKey('adopter_id', 'adopters', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('adoption_id', 'adoptions', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('home_visits', true);
    }

    public function down()
    {
        $this->forge->dropTable('home_visits', true);
        $this->forge->dropColumn('adoptions', 'monitoring_closed_at');
    }
}

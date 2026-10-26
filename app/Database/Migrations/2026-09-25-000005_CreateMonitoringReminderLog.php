<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMonitoringReminderLog extends Migration
{
    public function up()
    {
        $this->forge->addField(['id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true], 'adoption_id' => ['type' => 'INT', 'unsigned' => true], 'checkpoint' => ['type' => 'VARCHAR', 'constraint' => 20], 'sent_at' => ['type' => 'DATETIME']]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['adoption_id', 'checkpoint']);
        $this->forge->createTable('monitoring_reminder_log', true);
    }
    public function down() { $this->forge->dropTable('monitoring_reminder_log', true); }
}

<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCaptureVerificationToMonitoringSubmissions extends Migration
{
    public function up()
    {
        $fields = [
            'gps_accuracy' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'after' => 'gps_lng'],
            'capture_source' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'after' => 'gps_verified'],
            'captured_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'capture_source'],
            'device_info' => ['type' => 'TEXT', 'null' => true, 'after' => 'captured_at'],
            'user_agent' => ['type' => 'TEXT', 'null' => true, 'after' => 'device_info'],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true, 'after' => 'user_agent'],
        ];
        $this->forge->addColumn('monitoring_submissions', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('monitoring_submissions', ['gps_accuracy', 'capture_source', 'captured_at', 'device_info', 'user_agent', 'ip_address']);
    }
}

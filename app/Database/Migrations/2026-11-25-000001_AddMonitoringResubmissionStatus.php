<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMonitoringResubmissionStatus extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE monitoring_submissions MODIFY status ENUM('Verified', 'Pending', 'Flagged', 'Resubmission Requested') NOT NULL DEFAULT 'Pending'");
    }

    public function down()
    {
        $this->db->table('monitoring_submissions')->where('status', 'Resubmission Requested')->update(['status' => 'Flagged']);
        $this->db->query("ALTER TABLE monitoring_submissions MODIFY status ENUM('Verified', 'Pending', 'Flagged') NOT NULL DEFAULT 'Pending'");
    }
}

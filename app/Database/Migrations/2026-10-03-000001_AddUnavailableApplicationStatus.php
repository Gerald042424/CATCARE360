<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUnavailableApplicationStatus extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE adoption_applications MODIFY status ENUM('Under Review', 'Approved', 'Rejected', 'Unavailable') NOT NULL DEFAULT 'Under Review'");
        $this->db->query("ALTER TABLE adoption_application_cats MODIFY status ENUM('Under Review', 'Approved', 'Rejected', 'Unavailable') NOT NULL DEFAULT 'Under Review'");
    }

    public function down()
    {
        $this->db->query("UPDATE adoption_applications SET status = 'Rejected' WHERE status = 'Unavailable'");
        $this->db->query("UPDATE adoption_application_cats SET status = 'Rejected' WHERE status = 'Unavailable'");
        $this->db->query("ALTER TABLE adoption_applications MODIFY status ENUM('Under Review', 'Approved', 'Rejected') NOT NULL DEFAULT 'Under Review'");
        $this->db->query("ALTER TABLE adoption_application_cats MODIFY status ENUM('Under Review', 'Approved', 'Rejected') NOT NULL DEFAULT 'Under Review'");
    }
}

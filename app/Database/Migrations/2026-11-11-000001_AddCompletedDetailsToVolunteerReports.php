<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCompletedDetailsToVolunteerReports extends Migration
{
    public function up()
    {
        $fields = [];
        if (! $this->db->fieldExists('outcome', 'volunteer_reports')) $fields['outcome'] = ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true];
        if (! $this->db->fieldExists('checklist', 'volunteer_reports')) $fields['checklist'] = ['type' => 'TEXT', 'null' => true];
        if ($fields) $this->forge->addColumn('volunteer_reports', $fields);
    }

    public function down()
    {
        $fields = [];
        foreach (['outcome', 'checklist'] as $field) if ($this->db->fieldExists($field, 'volunteer_reports')) $fields[] = $field;
        if ($fields) $this->forge->dropColumn('volunteer_reports', $fields);
    }
}

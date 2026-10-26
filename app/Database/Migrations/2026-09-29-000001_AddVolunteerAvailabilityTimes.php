<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVolunteerAvailabilityTimes extends Migration
{
    public function up()
    {
        $fields = [];

        if (! $this->db->fieldExists('availability_start_time', 'volunteers')) {
            $fields['availability_start_time'] = ['type' => 'TIME', 'null' => true, 'after' => 'availability'];
        }
        if (! $this->db->fieldExists('availability_end_time', 'volunteers')) {
            $fields['availability_end_time'] = ['type' => 'TIME', 'null' => true, 'after' => 'availability_start_time'];
        }

        if ($fields !== []) {
            $this->forge->addColumn('volunteers', $fields);
        }
    }

    public function down()
    {
        foreach (['availability_end_time', 'availability_start_time'] as $field) {
            if ($this->db->fieldExists($field, 'volunteers')) {
                $this->forge->dropColumn('volunteers', $field);
            }
        }
    }
}

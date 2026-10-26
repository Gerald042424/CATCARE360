<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAnimalJourneyEvents extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'animal_id' => ['type' => 'INT', 'unsigned' => true],
            'event_type' => ['type' => 'VARCHAR', 'constraint' => 40],
            'title' => ['type' => 'VARCHAR', 'constraint' => 150],
            'description' => ['type' => 'TEXT', 'null' => true],
            'event_date' => ['type' => 'DATE'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['animal_id', 'event_date']);
        $this->forge->addForeignKey('animal_id', 'animals', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('animal_journey_events', true);
    }

    public function down()
    {
        $this->forge->dropTable('animal_journey_events', true);
    }
}

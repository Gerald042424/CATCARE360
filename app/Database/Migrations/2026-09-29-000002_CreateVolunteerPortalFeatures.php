<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateVolunteerPortalFeatures extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('volunteer_reports')) {
            $this->forge->addField(['id'=>['type'=>'INT','unsigned'=>true,'auto_increment'=>true], 'volunteer_id'=>['type'=>'INT','unsigned'=>true], 'task_id'=>['type'=>'INT','unsigned'=>true,'null'=>true], 'cat_name'=>['type'=>'VARCHAR','constraint'=>100,'null'=>true], 'health_status'=>['type'=>'VARCHAR','constraint'=>30], 'observations'=>['type'=>'TEXT'], 'photo_path'=>['type'=>'VARCHAR','constraint'=>255,'null'=>true], 'submitted_at'=>['type'=>'DATETIME']]);
            $this->forge->addKey('id',true); $this->forge->addForeignKey('volunteer_id','volunteers','id','CASCADE','CASCADE'); $this->forge->createTable('volunteer_reports',true);
        }
        if (! $this->db->tableExists('volunteer_applications')) {
            $this->forge->addField(['id'=>['type'=>'INT','unsigned'=>true,'auto_increment'=>true], 'first_name'=>['type'=>'VARCHAR','constraint'=>100], 'last_name'=>['type'=>'VARCHAR','constraint'=>100], 'email'=>['type'=>'VARCHAR','constraint'=>150], 'phone'=>['type'=>'VARCHAR','constraint'=>30], 'address'=>['type'=>'VARCHAR','constraint'=>255], 'city'=>['type'=>'VARCHAR','constraint'=>100], 'age'=>['type'=>'TINYINT','unsigned'=>true], 'available_days'=>['type'=>'VARCHAR','constraint'=>100], 'time_slot'=>['type'=>'VARCHAR','constraint'=>20], 'hours_per_week'=>['type'=>'VARCHAR','constraint'=>20,'null'=>true], 'start_date'=>['type'=>'DATE','null'=>true], 'has_animal_exp'=>['type'=>'VARCHAR','constraint'=>10], 'experience_details'=>['type'=>'TEXT','null'=>true], 'roles'=>['type'=>'VARCHAR','constraint'=>255], 'motivation'=>['type'=>'TEXT'], 'emergency_name'=>['type'=>'VARCHAR','constraint'=>150], 'emergency_relation'=>['type'=>'VARCHAR','constraint'=>50], 'emergency_phone'=>['type'=>'VARCHAR','constraint'=>30], 'status'=>['type'=>'VARCHAR','constraint'=>20,'default'=>'pending'], 'submitted_at'=>['type'=>'DATETIME']]);
            $this->forge->addKey('id', true); $this->forge->createTable('volunteer_applications', true);
        }
        if (! $this->db->tableExists('task_reassignment_requests')) {
            $this->forge->addField(['id'=>['type'=>'INT','unsigned'=>true,'auto_increment'=>true], 'task_id'=>['type'=>'INT','unsigned'=>true], 'volunteer_id'=>['type'=>'INT','unsigned'=>true], 'reason'=>['type'=>'VARCHAR','constraint'=>100], 'comments'=>['type'=>'TEXT','null'=>true], 'status'=>['type'=>'VARCHAR','constraint'=>20,'default'=>'pending'], 'created_at'=>['type'=>'DATETIME']]);
            $this->forge->addKey('id', true); $this->forge->addForeignKey('task_id','tasks','id','CASCADE','CASCADE'); $this->forge->addForeignKey('volunteer_id','volunteers','id','CASCADE','CASCADE'); $this->forge->createTable('task_reassignment_requests', true);
        }
        if (! $this->db->tableExists('task_report_drafts')) {
            $this->forge->addField(['id'=>['type'=>'INT','unsigned'=>true,'auto_increment'=>true], 'task_id'=>['type'=>'INT','unsigned'=>true], 'volunteer_id'=>['type'=>'INT','unsigned'=>true], 'outcome'=>['type'=>'VARCHAR','constraint'=>50,'null'=>true], 'notes'=>['type'=>'TEXT','null'=>true], 'checklist'=>['type'=>'TEXT','null'=>true], 'updated_at'=>['type'=>'DATETIME']]);
            $this->forge->addKey('id', true); $this->forge->addUniqueKey(['task_id','volunteer_id']); $this->forge->addForeignKey('task_id','tasks','id','CASCADE','CASCADE'); $this->forge->createTable('task_report_drafts', true);
        }
        $fields=[]; if (! $this->db->fieldExists('notif_email','volunteers')) $fields['notif_email']=['type'=>'TINYINT','default'=>1]; if (! $this->db->fieldExists('privacy_mode','volunteers')) $fields['privacy_mode']=['type'=>'TINYINT','default'=>0]; if ($fields) $this->forge->addColumn('volunteers',$fields);
    }
    public function down()
    { foreach (['task_report_drafts','task_reassignment_requests','volunteer_reports','volunteer_applications'] as $table) if ($this->db->tableExists($table)) $this->forge->dropTable($table, true); }
}

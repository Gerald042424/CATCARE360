<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class AddLiveCaptureToVolunteerReports extends Migration {
    public function up(){ $this->forge->addColumn('volunteer_reports',['gps_lat'=>['type'=>'DECIMAL','constraint'=>'10,8','null'=>true],'gps_lng'=>['type'=>'DECIMAL','constraint'=>'11,8','null'=>true],'gps_accuracy'=>['type'=>'DECIMAL','constraint'=>'10,2','null'=>true],'capture_source'=>['type'=>'VARCHAR','constraint'=>40,'null'=>true],'captured_at'=>['type'=>'DATETIME','null'=>true]]); }
    public function down(){ $this->forge->dropColumn('volunteer_reports',['gps_lat','gps_lng','gps_accuracy','capture_source','captured_at']); }
}

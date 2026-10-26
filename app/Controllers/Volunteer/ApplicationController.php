<?php
namespace App\Controllers\Volunteer;
use App\Controllers\BaseController;
use App\Models\VolunteerApplicationModel;
class ApplicationController extends BaseController {
 public function form(): string { return view('volunteer/apply', ['title'=>'Volunteer Application']); }
 public function submit() {
  $data=$this->request->getPost(); $days=(array)($data['available_days']??[]); $roles=(array)($data['roles']??[]);
  $required=['first_name','last_name','email','phone','address','city','age','time_slot','has_animal_exp','motivation','emergency_name','emergency_relation','emergency_phone'];
  foreach($required as $field) if(trim((string)($data[$field]??''))==='') return redirect()->back()->withInput()->with('error','Please complete all required fields.');
  if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || (int)$data['age']<18 || !$days || !$roles) return redirect()->back()->withInput()->with('error','Please provide a valid email, confirm you are at least 18, and select availability and roles.');
  (new VolunteerApplicationModel())->insert(['first_name'=>$data['first_name'],'last_name'=>$data['last_name'],'email'=>$data['email'],'phone'=>$data['phone'],'address'=>$data['address'],'city'=>$data['city'],'age'=>(int)$data['age'],'available_days'=>implode(',',$days),'time_slot'=>$data['time_slot'],'hours_per_week'=>$data['hours_per_week']?:null,'start_date'=>$data['start_date']?:null,'has_animal_exp'=>$data['has_animal_exp'],'experience_details'=>$data['experience_details']?:null,'roles'=>implode(',',$roles),'motivation'=>$data['motivation'],'emergency_name'=>$data['emergency_name'],'emergency_relation'=>$data['emergency_relation'],'emergency_phone'=>$data['emergency_phone'],'status'=>'pending','submitted_at'=>date('Y-m-d H:i:s')]);
  return redirect()->to('/volunteer/apply?success=1')->with('applicant_name',$data['first_name']);
 }
}

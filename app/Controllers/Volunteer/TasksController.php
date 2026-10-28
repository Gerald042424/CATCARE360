<?php

namespace App\Controllers\Volunteer;

use App\Controllers\BaseController;
use App\Models\TaskModel;
use App\Models\VolunteerReportModel;
use App\Models\TaskReportDraftModel;
use App\Models\TaskReassignmentRequestModel;
use App\Services\CloudStorageService;

class TasksController extends BaseController
{
    public function index(): string
    {
        $user = session('volunteer_user');
        $volunteerId = $user['volunteer_id'] ?? 0;
        $query = (new TaskModel())->select('tasks.*, animals.name as animal_name, animals.image_url')->join('animals', 'animals.id = tasks.animal_id', 'left')->where('assigned_to', $volunteerId);
        $search = trim((string) $this->request->getGet('q'));
        $status = trim((string) $this->request->getGet('status'));
        $category = trim((string) $this->request->getGet('category'));
        if ($search !== '') $query->groupStart()->like('tasks.title', $search)->orLike('tasks.description', $search)->orLike('animals.name', $search)->groupEnd();
        if ($category !== '') $query->where('tasks.category', $category);
        $tasks = $this->markOverdueTasks($query->orderBy('task_date')->orderBy('task_time')->findAll());
        if ($status !== '') $tasks = array_values(array_filter($tasks, static fn (array $task): bool => $task['status'] === $status));

        return view('volunteer/tasks', ['title' => 'Volunteer Tasks', 'tasks' => $tasks, 'search' => $search, 'selectedStatus' => $status, 'selectedCategory' => $category]);
    }

    public function schedule(): string
    {
        $volunteerId = (int) (session('volunteer_user')['volunteer_id'] ?? 0);
        $tasks = (new TaskModel())->select('tasks.*, animals.name as animal_name, animals.image_url')->join('animals', 'animals.id = tasks.animal_id', 'left')->where('assigned_to', $volunteerId)->where('task_date >=', date('Y-m-d'))->orderBy('task_date')->orderBy('task_time')->findAll();
        $tasks = $this->markOverdueTasks($tasks);
        return view('volunteer/schedule', ['title' => 'My Schedule', 'tasks' => $tasks]);
    }

    public function detail(int $id)
    {
        $model = new TaskModel();
        $volunteerId = (int) (session('volunteer_user')['volunteer_id'] ?? 0);
        $task = $model->select('tasks.*, animals.name as animal_name, animals.breed, animals.age, animals.color, animals.health, animals.cnvr_status, animals.location as cat_location, animals.behavior_notes, animals.medical_notes, animals.image_url')->join('animals', 'animals.id = tasks.animal_id', 'left')->where('tasks.id', $id)->where('tasks.assigned_to', $volunteerId)->first();
        if (! $task) return redirect()->to('/volunteer')->with('error', 'Task not found or is not assigned to you.');
        $task = $this->markOverdueTasks([$task])[0];
        if (! empty($task['image_url'])) {
            $task['image_url'] = base_url($task['image_url']);
        }
        if (in_array(strtoupper(trim((string) ($task['medical_notes'] ?? ''))), ['', 'N/A', 'NA', 'NONE'], true)) {
            $task['medical_notes'] = 'No medical notes recorded.';
        }

        if ($this->request->getMethod() === 'POST') {
            $model->update($id, ['status' => $this->request->getPost('status')]);

            return redirect()->to('/volunteer')->with('success', 'Task updated.');
        }

        return view('volunteer/task_detail', ['title' => 'Task Detail', 'task' => $task, 'draft' => (new TaskReportDraftModel())->where(['task_id'=>$id,'volunteer_id'=>$volunteerId])->first()]);
    }

    public function submitTaskReport(int $id) {
        $volunteerId=(int)(session('volunteer_user')['volunteer_id']??0); $task=(new TaskModel())->where(['id'=>$id,'assigned_to'=>$volunteerId])->first(); if(!$task) return redirect()->to('/volunteer')->with('error','Task not found.');
        $file=$this->request->getFile('photo'); $lat=filter_var($this->request->getPost('latitude'),FILTER_VALIDATE_FLOAT); $lng=filter_var($this->request->getPost('longitude'),FILTER_VALIDATE_FLOAT); $accuracy=filter_var($this->request->getPost('gps_accuracy'),FILTER_VALIDATE_FLOAT);
        if(!$file || !$file->isValid() || $file->hasMoved() || $this->request->getPost('capture_source') !== 'in_app_camera' || $lat===false || $lng===false || $accuracy===false) return redirect()->back()->withInput()->with('error','A live camera photo with current GPS location is required.');
        $info=@getimagesize($file->getTempName()); if($file->getMimeType()!=='image/jpeg' || !$info || $info[0]<320 || $info[1]<240 || $file->getSize()>10*1024*1024) return redirect()->back()->withInput()->with('error','Capture a valid JPEG photo (at least 320 × 240).');
        try { $photo=(new CloudStorageService())->store($file,'task-reports'); } catch (\Throwable $exception) { log_message('error','Task report photo upload failed: {message}',['message'=>$exception->getMessage()]); return redirect()->back()->withInput()->with('error','The report photo could not be saved. Please try again.'); }
        (new VolunteerReportModel())->insert(['volunteer_id'=>$volunteerId,'task_id'=>$id,'cat_name'=>null,'health_status'=>'good','observations'=>trim((string)$this->request->getPost('notes')),'photo_path'=>$photo,'gps_lat'=>$lat,'gps_lng'=>$lng,'gps_accuracy'=>$accuracy,'capture_source'=>'in_app_camera','captured_at'=>date('Y-m-d H:i:s'),'submitted_at'=>date('Y-m-d H:i:s')]);
        (new TaskModel())->update($id,['status'=>'Completed']); (new TaskReportDraftModel())->where(['task_id'=>$id,'volunteer_id'=>$volunteerId])->delete(); return redirect()->back()->with('submitted',true);
    }
    public function saveDraft(int $id) { $volunteerId=(int)(session('volunteer_user')['volunteer_id']??0); $model=new TaskReportDraftModel(); $existing=$model->where(['task_id'=>$id,'volunteer_id'=>$volunteerId])->first(); $data=['task_id'=>$id,'volunteer_id'=>$volunteerId,'outcome'=>$this->request->getPost('outcome'),'notes'=>$this->request->getPost('notes'),'checklist'=>json_encode((array)$this->request->getPost('checklist')),'updated_at'=>date('Y-m-d H:i:s')]; $existing?$model->update($existing['id'],$data):$model->insert($data); return $this->response->setJSON(['message'=>'Draft saved']); }

    public function requestReassignment(int $id)
    {
        $volunteerId = (int) (session('volunteer_user')['volunteer_id'] ?? 0);
        $task = (new TaskModel())->where(['id' => $id, 'assigned_to' => $volunteerId])->first();
        if (! $task) return redirect()->to('/volunteer')->with('error', 'Task not found.');
        $existing = (new TaskReassignmentRequestModel())->where(['task_id' => $id, 'volunteer_id' => $volunteerId, 'status' => 'Pending'])->first();
        if ($existing) return redirect()->back()->with('error', 'A reassignment request is already pending for this task.');
        (new TaskReassignmentRequestModel())->insert(['task_id' => $id, 'volunteer_id' => $volunteerId, 'reason' => trim((string) $this->request->getPost('reason')), 'comments' => trim((string) $this->request->getPost('comments')), 'status' => 'Pending', 'created_at' => date('Y-m-d H:i:s')]);
        return redirect()->to('/volunteer')->with('success', 'Reassignment request sent to the administrator.');
    }

    private function markOverdueTasks(array $tasks): array
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Manila'));
        foreach ($tasks as &$task) {
            if (! in_array($task['status'] ?? '', ['Pending', 'In Progress'], true) || empty($task['task_date']) || empty($task['task_time'])) continue;
            try {
                $dueAt = new \DateTimeImmutable($task['task_date'] . ' ' . $task['task_time'], new \DateTimeZone('Asia/Manila'));
                if ($dueAt < $now) $task['status'] = 'Overdue';
            } catch (\Exception) {
                // Keep legacy tasks with incomplete dates unchanged.
            }
        }
        unset($task);
        return $tasks;
    }
}

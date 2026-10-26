<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AnimalModel;
use App\Models\TaskModel;
use App\Models\VolunteerModel;
use App\Models\VolunteerReportModel;

class TasksController extends BaseController
{
    public function index(): string
    {
        $filters = [
            'search' => trim((string) $this->request->getGet('search')),
            'status' => trim((string) $this->request->getGet('status')),
            'priority' => trim((string) $this->request->getGet('priority')),
            'category' => trim((string) $this->request->getGet('category')),
            'volunteer' => trim((string) $this->request->getGet('volunteer')),
        ];

        $taskQuery = (new TaskModel())
            ->select('tasks.*, volunteers.name as volunteer_name, animals.name as animal_name')
            ->join('volunteers', 'volunteers.id = tasks.assigned_to', 'left')
            ->join('animals', 'animals.id = tasks.animal_id', 'left');
        $this->applyTaskFilters($taskQuery, $filters);
        $tasks = $taskQuery->orderBy('task_date', 'ASC')->orderBy('task_time', 'ASC')->findAll();

        $tasks = $this->markOverdueTasks($tasks);

        $requestedWeek = trim((string) $this->request->getGet('week'));
        $weekStart = preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedWeek)
            ? date('Y-m-d', strtotime('monday this week', strtotime($requestedWeek)))
            : date('Y-m-d', strtotime('monday this week'));
        $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));
        $scheduleTasks = (new TaskModel())
            ->select('tasks.*, volunteers.name as volunteer_name, animals.name as animal_name')
            ->join('volunteers', 'volunteers.id = tasks.assigned_to', 'left')
            ->join('animals', 'animals.id = tasks.animal_id', 'left')
            ->where('tasks.task_date >=', $weekStart)
            ->where('tasks.task_date <=', $weekEnd)
            ->orderBy('tasks.task_date', 'ASC')
            ->orderBy('tasks.task_time', 'ASC')
            ->findAll();
        $scheduleTasks = $this->markOverdueTasks($scheduleTasks);

        $reassignmentRequests = db_connect()->table('task_reassignment_requests')
            ->select('task_reassignment_requests.*, tasks.title AS task_title, tasks.task_date, tasks.task_time, tasks.location, volunteers.name AS volunteer_name')
            ->join('tasks', 'tasks.id = task_reassignment_requests.task_id')
            ->join('volunteers', 'volunteers.id = task_reassignment_requests.volunteer_id')
            ->where('task_reassignment_requests.status', 'Pending')->orderBy('task_reassignment_requests.created_at', 'DESC')->get()->getResultArray();

        // A report is the volunteer's proof/update after completing a task. It is
        // intentionally separate from the weekly schedule, which is only for
        // planning assignments.
        $taskUpdates = (new VolunteerReportModel())
            ->select('volunteer_reports.*, tasks.title AS task_title, tasks.category AS task_category, tasks.task_date, tasks.location, animals.name AS animal_name, volunteers.name AS volunteer_name')
            ->join('tasks', 'tasks.id = volunteer_reports.task_id', 'left')
            ->join('animals', 'animals.id = tasks.animal_id', 'left')
            ->join('volunteers', 'volunteers.id = volunteer_reports.volunteer_id', 'left')
            ->orderBy('volunteer_reports.submitted_at', 'DESC')
            ->findAll();

        return view('admin/tasks/index', [
            'title' => 'Tasks & Schedule',
            'tasks' => $tasks,
            'filters' => $filters,
            'week' => $this->groupWeek($scheduleTasks, $weekStart),
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'previousWeek' => date('Y-m-d', strtotime($weekStart . ' -7 days')),
            'nextWeek' => date('Y-m-d', strtotime($weekStart . ' +7 days')),
            'reassignmentRequests' => $reassignmentRequests,
            'taskUpdates' => $taskUpdates,
            'volunteers' => (new VolunteerModel())->where('status', 'Active')->orderBy('name')->findAll(),
        ]);
    }

    public function form(?int $id = null)
    {
        $model = new TaskModel();
        $task = $id ? $model->find($id) : null;

        if ($this->request->getMethod() === 'POST') {
            $data = $this->request->getPost(['title', 'description', 'category', 'priority', 'status', 'assigned_to', 'animal_id', 'task_date', 'task_time', 'location']);
            $data['assigned_to'] = $data['assigned_to'] ?: null;
            $data['animal_id'] = $data['animal_id'] ?: null;
            $previousVolunteerId = (int) ($task['assigned_to'] ?? 0);

            if (! $data['assigned_to']) {
                $data['assigned_to'] = $this->findFairVolunteer($data, $id);
            }

            if ($message = $this->assignmentAvailabilityError($data, $id)) {
                return redirect()->back()->withInput()->with('error', $message);
            }
            $taskId = $id ? (int) $id : (int) $model->insert($data, true);
            if ($id) {
                $model->update($taskId, $data);
            }

            $newVolunteerId = (int) ($data['assigned_to'] ?? 0);
            $emailSent = null;
            if ($newVolunteerId && $newVolunteerId !== $previousVolunteerId) {
                $emailSent = $this->sendAssignmentEmail($taskId, $newVolunteerId);
            }

            $message = 'Task saved.';
            if ($emailSent === true) {
                $message .= ' Assignment email sent to the volunteer.';
            } elseif ($emailSent === false) {
                $message .= ' The task appears in the volunteer portal, but the assignment email could not be sent.';
            }

            return redirect()->to('/admin/tasks')->with($emailSent === false ? 'error' : 'success', $message);
        }

        return view('admin/tasks/form', [
            'title' => $id ? 'Edit Task' : 'New Task',
            'task' => $task,
            'volunteers' => (new VolunteerModel())->orderBy('name')->findAll(),
            'animals' => (new AnimalModel())->orderBy('name')->findAll(),
        ]);
    }

    public function delete(int $id)
    {
        (new TaskModel())->delete($id);

        return redirect()->to('/admin/tasks')->with('success', 'Task deleted.');
    }

    public function resolveReassignment(int $requestId)
    {
        $request = db_connect()->table('task_reassignment_requests')->where(['id' => $requestId, 'status' => 'Pending'])->get()->getRowArray();
        $newVolunteerId = (int) $this->request->getPost('assigned_to');
        $task = $request ? (new TaskModel())->find($request['task_id']) : null;
        if (! $request || ! $task || ! $newVolunteerId) return redirect()->back()->with('error', 'Choose a replacement volunteer for this request.');
        $data = ['assigned_to' => $newVolunteerId, 'task_date' => $task['task_date'], 'task_time' => $task['task_time']];
        if ($message = $this->assignmentAvailabilityError($data, (int) $task['id'])) return redirect()->back()->with('error', $message);
        (new TaskModel())->update($task['id'], ['assigned_to' => $newVolunteerId, 'status' => 'Pending']);
        db_connect()->table('task_reassignment_requests')->where('id', $requestId)->update(['status' => 'Approved']);
        $emailSent = $this->sendAssignmentEmail((int) $task['id'], $newVolunteerId);
        $message = 'Task reassigned successfully.';
        if ($emailSent === true) $message .= ' Assignment email sent to the replacement volunteer.';
        if ($emailSent === false) $message .= ' The task appears in the volunteer portal, but the assignment email could not be sent.';
        return redirect()->to('/admin/tasks')->with($emailSent === false ? 'error' : 'success', $message);
    }

    private function groupWeek(array $tasks, string $weekStart): array
    {
        $week = [];
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime($weekStart . ' +' . $i . ' days'));
            $week[$date] = array_values(array_filter($tasks, static fn ($task) => $task['task_date'] === $date));
        }

        return $week;
    }

    private function applyTaskFilters($query, array $filters): void
    {
        if ($filters['search'] !== '') {
            $query->groupStart()
                ->like('tasks.title', $filters['search'])
                ->orLike('tasks.description', $filters['search'])
                ->orLike('animals.name', $filters['search'])
                ->orLike('volunteers.name', $filters['search'])
                ->groupEnd();
        }
        if ($filters['status'] !== '') $query->where('tasks.status', $filters['status']);
        if ($filters['priority'] !== '') $query->where('tasks.priority', $filters['priority']);
        if ($filters['category'] !== '') $query->where('tasks.category', $filters['category']);
        if ($filters['volunteer'] !== '') $query->where('tasks.assigned_to', (int) $filters['volunteer']);
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

    private function assignmentAvailabilityError(array $data, ?int $taskId): ?string
    {
        if (! $data['assigned_to']) {
            return null;
        }

        $volunteer = (new VolunteerModel())->find($data['assigned_to']);
        if (! $volunteer || ($volunteer['status'] ?? '') !== 'Active') {
            return 'Choose an active volunteer for this task.';
        }
        if (empty($data['task_date']) || empty($data['task_time'])) {
            return 'Set the task date and time before assigning a volunteer.';
        }

        $day = date('l', strtotime($data['task_date']));
        $availableDays = $this->normaliseAvailabilityDays((string) ($volunteer['availability'] ?? ''));
        if (! in_array($day, $availableDays, true)) {
            return sprintf('%s is not available on %s.', $volunteer['name'], $day);
        }

        $taskTime = substr((string) $data['task_time'], 0, 5);
        $start = substr((string) ($volunteer['availability_start_time'] ?? ''), 0, 5);
        $end = substr((string) ($volunteer['availability_end_time'] ?? ''), 0, 5);
        if ($start === '' || $end === '') {
            return sprintf('%s does not have an availability time set yet.', $volunteer['name']);
        }
        if ($taskTime < $start || $taskTime >= $end) {
            return sprintf('%s is available from %s to %s only.', $volunteer['name'], $start, $end);
        }

        $assignedTasks = (new TaskModel())
            ->where('assigned_to', $volunteer['id'])
            ->where('task_date', $data['task_date'])
            ->where('task_time', $data['task_time']);
        if ($taskId) {
            $assignedTasks->where('id !=', $taskId);
        }
        if ($assignedTasks->first()) {
            return sprintf('%s already has a task at %s on this date.', $volunteer['name'], $taskTime);
        }

        return null;
    }

    private function normaliseAvailabilityDays(string $availability): array
    {
        $map = ['Mon' => 'Monday', 'Tue' => 'Tuesday', 'Wed' => 'Wednesday', 'Thu' => 'Thursday', 'Fri' => 'Friday', 'Sat' => 'Saturday', 'Sun' => 'Sunday'];
        return array_values(array_filter(array_map(static function (string $day) use ($map): string {
            $day = trim($day);
            return $map[$day] ?? $day;
        }, explode(',', $availability))));
    }

    /** Assigns an unassigned task to the eligible volunteer with the lightest workload. */
    private function findFairVolunteer(array $data, ?int $taskId): ?int
    {
        if (empty($data['task_date']) || empty($data['task_time'])) return null;
        $volunteers = (new VolunteerModel())->where('status', 'Active')->findAll();
        usort($volunteers, static function (array $a, array $b): int {
            return ((int) $a['tasks_completed'] + (int) $a['pending_tasks']) <=> ((int) $b['tasks_completed'] + (int) $b['pending_tasks']);
        });
        foreach ($volunteers as $volunteer) {
            $candidate = $data;
            $candidate['assigned_to'] = $volunteer['id'];
            if ($this->assignmentAvailabilityError($candidate, $taskId) === null) return (int) $volunteer['id'];
        }
        return null;
    }

    /** Sends a volunteer only when a task is first assigned to them or reassigned to them. */
    private function sendAssignmentEmail(int $taskId, int $volunteerId): bool
    {
        $task = (new TaskModel())
            ->select('tasks.*, animals.name AS animal_name')
            ->join('animals', 'animals.id = tasks.animal_id', 'left')
            ->where('tasks.id', $taskId)
            ->first();
        $volunteer = (new VolunteerModel())->find($volunteerId);
        if (! $task || ! $volunteer || empty($volunteer['email'])) {
            log_message('error', 'Task assignment email could not be prepared for task {taskId} and volunteer {volunteerId}.', ['taskId' => $taskId, 'volunteerId' => $volunteerId]);
            return false;
        }

        $name = trim(explode(' ', (string) $volunteer['name'])[0]) ?: 'Volunteer';
        $date = ! empty($task['task_date']) ? date('F j, Y', strtotime($task['task_date'])) : 'To be confirmed';
        $time = ! empty($task['task_time']) ? date('g:i A', strtotime($task['task_time'])) : 'To be confirmed';
        $portalUrl = site_url('volunteer');
        $email = service('email');
        $email->setTo($volunteer['email']);
        $email->setSubject('New volunteer task assigned: ' . $task['title']);
        $email->setMailType('html');
        $email->setMessage('<!doctype html><html><body style="margin:0;padding:24px;background:#f4f7fb;font-family:Arial,sans-serif;color:#172033"><div style="max-width:560px;margin:auto;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 8px 28px rgba(29,78,216,.12)"><div style="padding:24px 30px;background:#2563eb;color:#fff;font-size:18px;font-weight:bold">🐾 CatCare 360</div><div style="padding:30px"><p style="font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;color:#2563eb">New task assigned</p><h1 style="font-size:25px;margin:8px 0 14px">Hi ' . esc($name) . '!</h1><p style="color:#4b5563;line-height:1.6">You have a new volunteer task. It is now available in your CatCare 360 volunteer portal.</p><div style="margin:24px 0;padding:20px;border-radius:14px;background:#eff6ff;border:1px solid #bfdbfe"><p style="margin:0 0 12px;font-size:12px;font-weight:bold;color:#4b6b9d">TASK DETAILS</p><p style="margin:0 0 10px;color:#172033"><strong>Task:</strong> ' . esc((string) $task['title']) . '</p><p style="margin:0 0 10px;color:#172033"><strong>Date & time:</strong> ' . esc($date . ' · ' . $time) . '</p><p style="margin:0 0 10px;color:#172033"><strong>Location:</strong> ' . esc((string) ($task['location'] ?: 'To be confirmed')) . '</p>' . (! empty($task['animal_name']) ? '<p style="margin:0;color:#172033"><strong>Cat:</strong> ' . esc((string) $task['animal_name']) . '</p>' : '') . '</div><p style="margin:24px 0 0"><a href="' . esc($portalUrl) . '" style="display:inline-block;padding:12px 18px;border-radius:10px;background:#2563eb;color:#fff;text-decoration:none;font-weight:bold">Open Volunteer Portal</a></p><p style="font-size:13px;color:#6b7280;line-height:1.6">Please sign in to view the complete task instructions and update its status.</p></div></div></body></html>');

        if (! $email->send()) {
            log_message('error', 'Task assignment email could not be sent to {email}.', ['email' => $volunteer['email']]);
            return false;
        }

        return true;
    }
}

<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AnimalModel;
use App\Models\AdoptionApplicationModel;
use App\Models\TaskModel;
use App\Models\VolunteerModel;

class DashboardController extends BaseController
{
    public function index(): string
    {
        $animals = new AnimalModel();
        helper('monitoring');
        $monitoringSnapshots = refreshActiveAdopterMonitoringCompliance();
        $startedScores = array_column(array_filter($monitoringSnapshots, static fn (array $snapshot): bool => $snapshot['has_started']), 'score');
        $compliance = empty($startedScores) ? null : (int) round(array_sum($startedScores) / count($startedScores));
        $applications = new AdoptionApplicationModel();
        $tasks = new TaskModel();
        $db = db_connect();

        return view('admin/dashboard/index', [
            'title' => 'Admin Dashboard',
            'stats' => [
                ['label' => 'Total Cats', 'value' => $animals->countAllResults(), 'icon' => 'paw-print', 'tone' => 'blue'],
                ['label' => 'Available', 'value' => $animals->where('status', 'Available')->countAllResults(), 'icon' => 'heart', 'tone' => 'emerald'],
                ['label' => 'Applications', 'value' => $applications->countAllResults(), 'icon' => 'file-text', 'tone' => 'amber'],
                ['label' => 'High Priority Tasks', 'value' => $tasks->where('priority', 'High')->countAllResults(), 'icon' => 'alert-circle', 'tone' => 'red'],
            ],
            'recentApplications' => $applications->select('adoption_applications.full_name AS applicant_name, adoption_applications.compatibility_score, adoption_applications.status, adoption_applications.submitted_at, animals.name AS animal_name')
                ->join('animals', 'animals.id = adoption_applications.cat_id', 'left')->orderBy('submitted_at', 'DESC')->limit(5)->findAll(),
            'tasks' => $tasks->select('tasks.*, volunteers.name as volunteer_name')->join('volunteers', 'volunteers.id = tasks.assigned_to', 'left')->orderBy('task_date', 'ASC')->limit(5)->find(),
            'compliance' => $compliance,
            'complianceMonitoredCount' => count($startedScores),
            'pipeline' => [
                ['label' => 'Intake', 'value' => $animals->countAllResults(), 'tone' => 'blue'],
                ['label' => 'Applications', 'value' => $applications->countAllResults(), 'tone' => 'amber'],
                ['label' => 'Approved', 'value' => $applications->where('status', 'Approved')->countAllResults(), 'tone' => 'violet'],
                ['label' => 'Adopted', 'value' => $db->table('adoptions')->where('status', 'Active')->countAllResults(), 'tone' => 'emerald'],
            ],
        ]);
    }
}

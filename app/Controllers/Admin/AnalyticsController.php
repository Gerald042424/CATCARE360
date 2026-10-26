<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class AnalyticsController extends BaseController
{
    public function index(): string
    {
        $db = db_connect();
        $assessment = $db->table('adoption_applications')->select('AVG(assessment_score) AS average_score, SUM(assessment_score >= 80) AS strong, SUM(assessment_score >= 60 AND assessment_score < 80) AS watch, SUM(assessment_score < 60) AS risk, SUM(assessment_score IS NULL) AS provisional, SUM(indoor_living_compliance != \'Meets Requirements\' OR humane_housing_compliance != \'Meets Requirements\') AS welfare_concerns')->get()->getRowArray() ?: [];
        return view('admin/analytics/index', [
            'title' => 'Analytics Dashboard',
            'summary' => [
                ['label' => 'Cats in records', 'value' => $db->table('animals')->countAllResults(), 'tone' => 'blue'],
                ['label' => 'Active adoptions', 'value' => $db->table('adoptions')->where('status', 'Active')->countAllResults(), 'tone' => 'emerald'],
                ['label' => 'Monitoring updates', 'value' => $db->table('monitoring_submissions')->countAllResults(), 'tone' => 'violet'],
                ['label' => 'Completed tasks', 'value' => $db->table('tasks')->where('status', 'Completed')->countAllResults(), 'tone' => 'amber'],
            ],
            'applicationBreakdown' => $db->table('adoption_applications')->select('status, COUNT(*) AS total')->groupBy('status')->orderBy('total', 'DESC')->get()->getResultArray(),
            'monitoringBreakdown' => $db->table('monitoring_submissions')->select('status, COUNT(*) AS total')->groupBy('status')->orderBy('total', 'DESC')->get()->getResultArray(),
            'monthlyAdoptions' => $db->table('adoptions')->select("DATE_FORMAT(adoption_date, '%b %Y') AS month, COUNT(*) AS total")->groupBy("DATE_FORMAT(adoption_date, '%Y-%m')")->orderBy('adoption_date', 'DESC')->limit(6)->get()->getResultArray(),
            'assessment' => ['average' => round((float) ($assessment['average_score'] ?? 0), 2), 'strong' => (int) ($assessment['strong'] ?? 0), 'watch' => (int) ($assessment['watch'] ?? 0), 'risk' => (int) ($assessment['risk'] ?? 0), 'provisional' => (int) ($assessment['provisional'] ?? 0), 'welfareConcerns' => (int) ($assessment['welfare_concerns'] ?? 0)],
        ]);
    }
}

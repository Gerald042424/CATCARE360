<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class AnalyticsController extends BaseController
{
    public function index(): string
    {
        $db = db_connect();
        $assessment = $db->table('adoption_applications')->select('AVG(assessment_score) AS average_score, SUM(assessment_score >= 80) AS strong, SUM(assessment_score >= 60 AND assessment_score < 80) AS watch, SUM(assessment_score < 60) AS risk, SUM(assessment_score IS NULL) AS provisional, SUM(indoor_living_compliance != \'Meets Requirements\' OR humane_housing_compliance != \'Meets Requirements\') AS welfare_concerns')->get()->getRowArray() ?: [];
        $monthlyRows = $db->query(
            'SELECT YEAR(adoption_date) AS adoption_year, '
            . 'MONTH(adoption_date) AS adoption_month, COUNT(*) AS total '
            . 'FROM adoptions WHERE adoption_date IS NOT NULL '
            . 'GROUP BY YEAR(adoption_date), MONTH(adoption_date) '
            . 'ORDER BY adoption_year DESC, adoption_month DESC LIMIT 6'
        )->getResultArray();
        $monthlyAdoptions = array_map(static function (array $row): array {
            $year = (int) ($row['adoption_year'] ?? 0);
            $month = (int) ($row['adoption_month'] ?? 0);
            $timestamp = $year > 0 && $month >= 1 && $month <= 12
                ? mktime(0, 0, 0, $month, 1, $year)
                : false;

            return [
                'month' => $timestamp === false ? 'Unknown date' : date('M Y', $timestamp),
                'total' => (int) ($row['total'] ?? 0),
            ];
        }, $monthlyRows);

        return view('admin/analytics/index', [
            'title' => 'Analytics Dashboard',
            'summary' => [
                ['label' => 'Cats in records', 'value' => $db->table('animals')->countAllResults(), 'tone' => 'text-blue-600', 'description' => 'All cat profiles across every status. Open Cat Records to see which cats are available.', 'source' => 'Cat Records', 'path' => 'admin/animals'],
                ['label' => 'Active adoptions', 'value' => $db->table('adoptions')->where('status', 'Active')->countAllResults(), 'tone' => 'text-emerald-600', 'description' => 'Adoption records currently marked Active. Use this count to see how many adoptions are ongoing.', 'source' => 'Adoption Register', 'path' => 'admin/reports/preview/adoptions'],
                ['label' => 'Monitoring updates', 'value' => $db->table('monitoring_submissions')->countAllResults(), 'tone' => 'text-violet-600', 'description' => 'All post-adoption check-ins submitted by adopters, across all review statuses.', 'source' => 'Monitoring', 'path' => 'admin/monitoring'],
                ['label' => 'Completed tasks', 'value' => $db->table('tasks')->where('status', 'Completed')->countAllResults(), 'tone' => 'text-amber-600', 'description' => 'Volunteer tasks marked Completed. Use this count to track how much assigned work has been finished.', 'source' => 'Tasks & Schedule', 'path' => 'admin/tasks'],
            ],
            'applicationBreakdown' => $db->table('adoption_applications')->select('status, COUNT(*) AS total')->groupBy('status')->orderBy('total', 'DESC')->get()->getResultArray(),
            'monitoringBreakdown' => $db->table('monitoring_submissions')->select('status, COUNT(*) AS total')->groupBy('status')->orderBy('total', 'DESC')->get()->getResultArray(),
            'monthlyAdoptions' => $monthlyAdoptions,
            'assessment' => ['average' => isset($assessment['average_score']) ? (float) $assessment['average_score'] : null, 'strong' => (int) ($assessment['strong'] ?? 0), 'watch' => (int) ($assessment['watch'] ?? 0), 'risk' => (int) ($assessment['risk'] ?? 0), 'provisional' => (int) ($assessment['provisional'] ?? 0), 'welfareConcerns' => (int) ($assessment['welfare_concerns'] ?? 0)],
        ]);
    }
}

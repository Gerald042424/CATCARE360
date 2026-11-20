<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\Database\BaseBuilder;

class ReportsController extends BaseController
{
    public function index(): string
    {
        $db = db_connect();
        return view('admin/reports/index', ['title' => 'Reports', 'reports' => [
            ['type' => 'adoptions', 'title' => 'Adoption Register', 'description' => 'All active and historical adoption records.', 'count' => $db->table('adoptions')->countAllResults()],
            ['type' => 'monitoring', 'title' => 'Monitoring Verification', 'description' => 'Geo-tagged post-adoption updates and review status.', 'count' => $db->table('monitoring_submissions')->countAllResults()],
            ['type' => 'compliance', 'title' => 'Adopter Compliance', 'description' => 'Current compliance score and follow-up indicators.', 'count' => $db->table('adopters')->countAllResults()],
        ]]);
    }

    public function preview(string $type)
    {
        $report = $this->report($type);
        if ($report === null) {
            return redirect()->to('/admin/reports')->with('error', 'Unknown report type.');
        }

        [$title, $headers, $query] = $report;
        $pageSize = 25;
        $count = $query->countAllResults(false);
        $lastPage = max(1, (int) ceil($count / $pageSize));
        $requestedPage = filter_var($this->request->getGet('page'), FILTER_VALIDATE_INT);
        $page = min($lastPage, max(1, (int) $requestedPage));

        return view('admin/reports/preview', [
            'title' => $title,
            'type' => $type,
            'headers' => $headers,
            'rows' => $query->get($pageSize, ($page - 1) * $pageSize)->getResultArray(),
            'count' => $count,
            'page' => $page,
            'lastPage' => $lastPage,
        ]);
    }

    public function export(string $type)
    {
        $report = $this->report($type);
        if ($report === null) {
            return redirect()->to('/admin/reports')->with('error', 'Unknown report type.');
        }

        [, $headers, $query] = $report;
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, $headers);
        foreach ($query->get()->getResultArray() as $row) {
            fputcsv($stream, array_values($row));
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        $filename = $type . '-report-' . date('Ymd') . '.csv';
        return $this->response
            ->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csv);
    }

    /** @return array{string, list<string>, BaseBuilder}|null */
    private function report(string $type): ?array
    {
        $db = db_connect();
        if ($type === 'adoptions') {
            $headers = ['Adopter', 'Email', 'Cat', 'Adoption Date', 'Status'];
            $query = $db->table('adoptions')->select('adopters.name AS adopter, adopters.email, animals.name AS cat, adoptions.adoption_date, adoptions.status')->join('adopters', 'adopters.id = adoptions.adopter_id', 'left')->join('animals', 'animals.id = adoptions.animal_id', 'left')->orderBy('adoptions.adoption_date', 'DESC')->orderBy('adoptions.id', 'DESC');
            return ['Adoption Register', $headers, $query];
        }

        if ($type === 'monitoring') {
            $headers = ['Adopter', 'Cat', 'Checkpoint', 'Submitted At', 'GPS Verified', 'Review Status'];
            $query = $db->table('monitoring_submissions')->select('adopters.name AS adopter, animals.name AS cat, monitoring_submissions.checkpoint, monitoring_submissions.submission_date, monitoring_submissions.gps_verified, monitoring_submissions.status')->join('adopters', 'adopters.id = monitoring_submissions.adopter_id', 'left')->join('adoptions', 'adoptions.id = monitoring_submissions.adoption_id', 'left')->join('animals', 'animals.id = adoptions.animal_id', 'left')->orderBy('monitoring_submissions.submission_date', 'DESC')->orderBy('monitoring_submissions.id', 'DESC');
            return ['Monitoring Verification', $headers, $query];
        }

        if ($type === 'compliance') {
            helper('monitoring');
            refreshActiveAdopterMonitoringCompliance();
            $headers = ['Adopter', 'Email', 'Compliance Score', 'Total Submissions', 'GPS Verified', 'Flag'];
            $query = $db->table('adopters')->select('name, email, compliance_score, total_submissions, gps_verified_count, flag')->orderBy('compliance_score', 'DESC')->orderBy('id', 'DESC');
            return ['Adopter Compliance', $headers, $query];
        }

        return null;
    }
}

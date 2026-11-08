<?php

namespace App\Controllers;

use App\Services\MonitoringReminderService;
use CodeIgniter\HTTP\ResponseInterface;

final class CronController extends BaseController
{
    public function monitoringReminders(): ResponseInterface
    {
        $secret = (string) getenv('CRON_SECRET');
        $authorization = $this->request->getHeaderLine('Authorization');

        if ($secret === '') {
            log_message('critical', 'CRON_SECRET is not configured; monitoring reminder cron was blocked.');

            return $this->response
                ->setStatusCode(503)
                ->setHeader('Cache-Control', 'no-store')
                ->setJSON(['ok' => false, 'error' => 'Cron is not configured.']);
        }

        if (! hash_equals('Bearer ' . $secret, $authorization)) {
            return $this->response
                ->setStatusCode(401)
                ->setHeader('Cache-Control', 'no-store')
                ->setJSON(['ok' => false, 'error' => 'Unauthorized.']);
        }

        try {
            $summary = (new MonitoringReminderService())->run();

            return $this->response
                ->setHeader('Cache-Control', 'no-store')
                ->setJSON(['ok' => true, 'summary' => $summary]);
        } catch (\Throwable $exception) {
            log_message('error', 'Monitoring reminder cron failed: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return $this->response
                ->setStatusCode(500)
                ->setHeader('Cache-Control', 'no-store')
                ->setJSON(['ok' => false, 'error' => 'Reminder run failed.']);
        }
    }
}

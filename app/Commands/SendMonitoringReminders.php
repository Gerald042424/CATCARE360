<?php

namespace App\Commands;

use App\Services\MonitoringReminderService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SendMonitoringReminders extends BaseCommand
{
    protected $group = 'Monitoring';
    protected $name = 'monitoring:send-reminders';
    protected $description = 'Sends one-day-before reminders for 3-day, 3-week, and 3-month adoption updates.';

    public function run(array $params)
    {
        $summary = (new MonitoringReminderService())->run();
        CLI::write(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 'green');
    }
}

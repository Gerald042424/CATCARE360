<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SendMonitoringReminders extends BaseCommand
{
    protected $group = 'Monitoring';
    protected $name = 'monitoring:send-reminders';
    protected $description = 'Sends one-day-before reminders for 3-day, 3-week, and 3-month adoption updates.';

    public function run(array $params)
    {
        helper('monitoring');
        $db = db_connect();
        $adoptions = $db->table('adoptions')->select('adoptions.*, adopters.name AS adopter_name, adopters.email, animals.name AS cat_name')->join('adopters', 'adopters.id = adoptions.adopter_id')->join('animals', 'animals.id = adoptions.animal_id')->where('adoptions.status', 'Active')->get()->getResultArray();
        // This command is scheduled daily. Refresh flags before sending reminders
        // so overdue checkpoints immediately appear as WANTED/MISSING and in Home Visits.
        foreach (array_unique(array_column($adoptions, 'adopter_id')) as $adopterId) {
            recalculateAdopterMonitoringCompliance((int) $adopterId);
        }
        $sent = 0;
        foreach ($adoptions as $adoption) {
            foreach (monitoringCheckpoints($adoption['adoption_date']) as $checkpoint => $dueDate) {
                if (date('Y-m-d') !== date('Y-m-d', strtotime($dueDate . ' -1 day'))) continue;
                if ($db->table('monitoring_reminder_log')->where(['adoption_id' => $adoption['id'], 'checkpoint' => $checkpoint])->countAllResults() > 0) continue;
                $email = service('email'); $config = config('Email');
                $email->setFrom($config->fromEmail ?: 'no-reply@catcare360.org', $config->fromName ?: 'CatCare 360');
                $email->setTo($adoption['email']); $email->setSubject("Reminder: {$checkpoint} update for {$adoption['cat_name']}"); $email->setMailType('html');
                $email->setMessage('<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;padding:28px;border:1px solid #e5e7eb;border-radius:16px"><h2 style="color:#2563eb">🐾 CatCare 360 reminder</h2><p>Hello ' . esc($adoption['adopter_name']) . ',</p><p>Your <strong>' . esc($checkpoint) . '</strong> update for <strong>' . esc($adoption['cat_name']) . '</strong> is due tomorrow (' . esc(date('F d, Y', strtotime($dueDate))) . '). Please sign in and submit a photo and wellbeing update.</p><a style="display:inline-block;padding:12px 16px;background:#2563eb;color:white;text-decoration:none;border-radius:8px" href="' . site_url('adopter/portal') . '">Submit update</a></div>');
                if ($email->send()) { $db->table('monitoring_reminder_log')->insert(['adoption_id' => $adoption['id'], 'checkpoint' => $checkpoint, 'sent_at' => date('Y-m-d H:i:s')]); ++$sent; }
            }
        }
        CLI::write("Refreshed compliance for " . count(array_unique(array_column($adoptions, 'adopter_id'))) . " active adopter(s).", 'green');
        CLI::write("Sent {$sent} monitoring reminder(s).", 'green');
    }
}

<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

final class MonitoringReminderService
{
    public function __construct(private ?BaseConnection $db = null)
    {
        $this->db ??= db_connect();
    }

    /**
     * Send one reminder per adoption checkpoint.
     *
     * The normal reminder is sent one day before the due date. If the
     * scheduler was unavailable on that day, the first later run sends a
     * catch-up overdue reminder instead. The existing unique reminder log
     * prevents duplicate emails for the same adoption and checkpoint.
     *
     * @return array<string, int|string>
     */
    public function run(?string $today = null): array
    {
        helper('monitoring');

        $today = $today ?: date('Y-m-d');
        $adoptions = $this->db->table('adoptions')
            ->select('adoptions.*, adopters.name AS adopter_name, adopters.email, animals.name AS cat_name')
            ->join('adopters', 'adopters.id = adoptions.adopter_id')
            ->join('animals', 'animals.id = adoptions.animal_id')
            ->where('adoptions.status', 'Active')
            ->get()
            ->getResultArray();

        $adopterIds = array_values(array_unique(array_map(
            static fn (array $adoption): int => (int) $adoption['adopter_id'],
            $adoptions,
        )));

        foreach ($adopterIds as $adopterId) {
            recalculateAdopterMonitoringCompliance($adopterId);
        }

        $summary = [
            'date' => $today,
            'active_adoptions' => count($adoptions),
            'refreshed_adopters' => count($adopterIds),
            'due_tomorrow' => 0,
            'due_today_catchups' => 0,
            'overdue_catchups' => 0,
            'already_submitted' => 0,
            'already_notified' => 0,
            'sent' => 0,
            'failed' => 0,
        ];

        foreach ($adoptions as $adoption) {
            foreach (monitoringCheckpoints($adoption['adoption_date']) as $checkpoint => $dueDate) {
                $alreadySubmitted = $this->db->table('monitoring_submissions')->where([
                    'adoption_id' => $adoption['id'],
                    'checkpoint' => $checkpoint,
                ])->countAllResults() > 0;

                if ($alreadySubmitted) {
                    ++$summary['already_submitted'];
                    continue;
                }

                $alreadyNotified = $this->db->table('monitoring_reminder_log')->where([
                    'adoption_id' => $adoption['id'],
                    'checkpoint' => $checkpoint,
                ])->countAllResults() > 0;

                if ($alreadyNotified) {
                    ++$summary['already_notified'];
                    continue;
                }

                $notificationType = self::notificationType($dueDate, $today);
                if ($notificationType === null) {
                    continue;
                }

                if ($notificationType === 'due_tomorrow') {
                    ++$summary['due_tomorrow'];
                } elseif ($notificationType === 'due_today') {
                    ++$summary['due_today_catchups'];
                } else {
                    ++$summary['overdue_catchups'];
                }

                if (! $this->sendReminder($adoption, $checkpoint, $dueDate, $notificationType)) {
                    ++$summary['failed'];
                    log_message('error', 'Monitoring reminder failed for adoption {adoptionId}, checkpoint {checkpoint}.', [
                        'adoptionId' => $adoption['id'],
                        'checkpoint' => $checkpoint,
                    ]);
                    continue;
                }

                try {
                    $this->db->table('monitoring_reminder_log')->insert([
                        'adoption_id' => $adoption['id'],
                        'checkpoint' => $checkpoint,
                        'sent_at' => date('Y-m-d H:i:s'),
                    ]);
                    ++$summary['sent'];
                } catch (\Throwable $exception) {
                    // A concurrent invocation may have inserted the same unique
                    // adoption/checkpoint pair. The email was sent successfully,
                    // so keep the run successful and record the logging conflict.
                    log_message('warning', 'Monitoring reminder was sent but its log could not be inserted: {message}', [
                        'message' => $exception->getMessage(),
                    ]);
                }
            }
        }

        log_message('info', 'Monitoring reminder run completed: {summary}', [
            'summary' => json_encode($summary, JSON_UNESCAPED_SLASHES),
        ]);

        return $summary;
    }

    public static function notificationType(string $dueDate, string $today): ?string
    {
        $due = strtotime($dueDate);
        $current = strtotime($today);

        if ($due === false || $current === false) {
            return null;
        }

        if ($today === date('Y-m-d', strtotime($dueDate . ' -1 day'))) {
            return 'due_tomorrow';
        }

        if ($today === $dueDate) {
            return 'due_today';
        }

        return $current > $due ? 'overdue' : null;
    }

    /**
     * @param array<string, mixed> $adoption
     */
    private function sendReminder(array $adoption, string $checkpoint, string $dueDate, string $type): bool
    {
        $email = service('email');
        $config = config('Email');
        $email->setFrom($config->fromEmail ?: 'no-reply@catcare360.org', $config->fromName ?: 'CatCare 360');
        $email->setTo((string) $adoption['email']);
        $email->setMailType('html');

        $safeName = esc((string) $adoption['adopter_name']);
        $safeCat = esc((string) $adoption['cat_name']);
        $safeCheckpoint = esc($checkpoint);
        $formattedDueDate = esc(date('F d, Y', strtotime($dueDate)));

        if ($type === 'overdue') {
            $email->setSubject("Action needed: overdue {$checkpoint} update for {$adoption['cat_name']}");
            $heading = 'Your monitoring update is overdue';
            $message = "Your <strong>{$safeCheckpoint}</strong> update for <strong>{$safeCat}</strong> was due on <strong>{$formattedDueDate}</strong>. Please submit a recent photo and wellbeing update as soon as possible so the community team can confirm the cat's welfare.";
            $accent = '#b45309';
        } elseif ($type === 'due_today') {
            $email->setSubject("Due today: {$checkpoint} update for {$adoption['cat_name']}");
            $heading = 'Your monitoring update is due today';
            $message = "Your <strong>{$safeCheckpoint}</strong> update for <strong>{$safeCat}</strong> is due today (<strong>{$formattedDueDate}</strong>). Please submit a recent photo and wellbeing update.";
            $accent = '#b45309';
        } else {
            $email->setSubject("Reminder: {$checkpoint} update for {$adoption['cat_name']}");
            $heading = 'A monitoring update is due tomorrow';
            $message = "Your <strong>{$safeCheckpoint}</strong> update for <strong>{$safeCat}</strong> is due tomorrow (<strong>{$formattedDueDate}</strong>). Please submit a recent photo and wellbeing update.";
            $accent = '#0a6a8a';
        }

        $email->setMessage('<!doctype html><html><body style="margin:0;background:#f4f7fb;font-family:Arial,sans-serif;color:#172033;padding:28px 16px"><div style="max-width:580px;margin:auto;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 8px 28px rgba(10,106,138,.12)"><div style="background:#0a6a8a;color:#fff;padding:24px 30px;font-weight:bold;font-size:18px">🐾 CatCare 360 · McKinley West Cats</div><div style="padding:30px"><p style="margin:0 0 10px;color:' . $accent . ';font-size:13px;text-transform:uppercase;letter-spacing:.08em;font-weight:bold">Post-adoption monitoring</p><h1 style="margin:0 0 18px;font-size:24px;color:#0f172a">' . $heading . '</h1><p style="line-height:1.7;color:#475569">Hello ' . $safeName . ',</p><p style="line-height:1.7;color:#475569">' . $message . '</p><p style="margin:24px 0"><a href="' . site_url('adopter/portal/' . (int) $adoption['id']) . '" style="display:inline-block;padding:12px 17px;background:#0a6a8a;color:#fff;text-decoration:none;border-radius:9px;font-weight:bold">Submit monitoring update</a></p><p style="line-height:1.7;color:#64748b;font-size:13px">Thank you for helping McKinley West Cats keep every adopted cat safe and well.</p></div><div style="padding:16px 30px;background:#f8fafc;color:#94a3b8;font-size:12px">CatCare 360 · Post-adoption monitoring</div></div></body></html>');

        return $email->send();
    }
}

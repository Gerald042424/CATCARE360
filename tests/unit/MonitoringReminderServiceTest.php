<?php

use App\Services\MonitoringReminderService;
use CodeIgniter\Test\CIUnitTestCase;

final class MonitoringReminderServiceTest extends CIUnitTestCase
{
    public function testReminderIsDueOneDayBeforeCheckpoint(): void
    {
        $this->assertSame(
            'due_tomorrow',
            MonitoringReminderService::notificationType('2026-11-05', '2026-11-04'),
        );
    }

    public function testMissedSchedulerRunProducesOverdueCatchup(): void
    {
        $this->assertSame(
            'overdue',
            MonitoringReminderService::notificationType('2026-11-05', '2026-11-08'),
        );
    }

    public function testNoNotificationIsSentBeforeReminderDate(): void
    {
        $this->assertNull(
            MonitoringReminderService::notificationType('2026-11-05', '2026-11-03'),
        );
    }

    public function testDueDateItselfUsesDueTodayCatchupWhenReminderWasMissed(): void
    {
        $this->assertSame(
            'due_today',
            MonitoringReminderService::notificationType('2026-11-05', '2026-11-05'),
        );
    }
}

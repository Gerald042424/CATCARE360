<?php

use App\Services\AuthSessionPolicy;
use PHPUnit\Framework\TestCase;

final class AuthSessionPolicyTest extends TestCase
{
    private AuthSessionPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new AuthSessionPolicy();
    }

    public function testStaffSessionExpiresAtFifteenIdleMinutes(): void
    {
        self::assertNull($this->policy->expirationReason(1899, 1000, 1000, 900, 28800));
        self::assertSame('idle', $this->policy->expirationReason(1900, 1000, 1000, 900, 28800));
    }

    public function testAdopterAndVolunteerSessionsExpireAtThirtyIdleMinutes(): void
    {
        self::assertNull($this->policy->expirationReason(2799, 1000, 1000, 1800, 28800));
        self::assertSame('idle', $this->policy->expirationReason(2800, 1000, 1000, 1800, 28800));
    }

    public function testAbsoluteLimitWinsEvenWithRecentActivity(): void
    {
        self::assertSame('absolute', $this->policy->expirationReason(29800, 1000, 29799, 1800, 28800));
    }
}

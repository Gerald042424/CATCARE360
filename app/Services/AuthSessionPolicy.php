<?php

namespace App\Services;

final class AuthSessionPolicy
{
    public function expirationReason(
        int $now,
        int $startedAt,
        int $lastActivityAt,
        int $idleSeconds,
        int $absoluteSeconds
    ): ?string {
        if ($startedAt > 0 && ($now - $startedAt) >= $absoluteSeconds) {
            return 'absolute';
        }

        if ($lastActivityAt > 0 && ($now - $lastActivityAt) >= $idleSeconds) {
            return 'idle';
        }

        return null;
    }
}

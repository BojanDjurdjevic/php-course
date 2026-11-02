<?php

namespace App\Security;

use PDO;

final class RateLimiter
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function allowPerHour(
        string $scope,
        string $identifier,
        int $maxAttempts
    ): bool
    {
        if ($maxAttempts < 1) {
            throw new \InvalidArgumentException(
                'maxAttempts must be positive.'
            );
        }

        $keyHash = hash('sha256', $identifier);

        $stmt = $this->pdo->prepare(
            "INSERT INTO request_rate_limits
                (scope, key_hash, attempts, window_started_at)
             VALUES
                (:scope, :key_hash, 1, UTC_TIMESTAMP())

             ON DUPLICATE KEY UPDATE

                attempts = IF(
                    window_started_at <= UTC_TIMESTAMP() - INTERVAL 1 HOUR,
                    1,
                    attempts + 1
                ),

                window_started_at = IF(
                    window_started_at <= UTC_TIMESTAMP() - INTERVAL 1 HOUR,
                    UTC_TIMESTAMP(),
                    window_started_at
                )"
        );

        $stmt->execute([
            'scope' => $scope,
            'key_hash' => $keyHash
        ]);

        $stmt = $this->pdo->prepare(
            "SELECT attempts
             FROM request_rate_limits
             WHERE scope = :scope
             AND key_hash = :key_hash"
        );

        $stmt->execute([
            'scope' => $scope,
            'key_hash' => $keyHash
        ]);

        $attempts = (int) $stmt->fetchColumn();

        return $attempts <= $maxAttempts;
    }
}
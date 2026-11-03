<?php

namespace App\Repositories;

use App\Contracts\PasswordResetTokenRepository;
use Override;
use PDO;

final class PdoPasswordResetTokenRepository implements PasswordResetTokenRepository {
    public function __construct(private PDO $pdo) {}

    #[Override]
    public function storeResetHash(int $userId, string $hash, string $expiresAt): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO password_reset_tokens
                (user_id, token_hash, expires_at, created_at)
            VALUES
                (:user_id, :insert_hash, :insert_expires, UTC_TIMESTAMP())
            ON DUPLICATE KEY UPDATE
                token_hash = :update_hash,
                expires_at = :update_expires,
                created_at = UTC_TIMESTAMP()"
        );

        return $stmt->execute([
            'user_id' => $userId,
            'insert_hash' => $hash,
            'insert_expires' => $expiresAt,
            'update_hash' => $hash,
            'update_expires' => $expiresAt,
        ]);
    }

    #[Override]
    public function findToken(string $hash): ?object
    {
        return $this->fetchToken($hash, false);
    }

    #[Override]
    public function findTokenForUpdate(string $hash): ?object
    {
        if (!$this->pdo->inTransaction()) {
            throw new \LogicException(
                'findTokenForUpdate requires an active transaction.'
            );
        }

        return $this->fetchToken($hash, true);
    }

    #[Override]
    public function deleteByUserId(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM password_reset_tokens WHERE user_id = :user_id"
        );

        $stmt->execute(['user_id' => $userId]);
    }

    private function fetchToken(string $hash, bool $lock): ?object 
    {
        $sql = "SELECT id, user_id, token_hash, expires_at
                FROM password_reset_tokens
                WHERE token_hash = :hash";
        
        if($lock) $sql .= " FOR UPDATE";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            'hash' => $hash
        ]);

        $data = $stmt->fetch(PDO::FETCH_OBJ);

        return $data === false ? null : $data;
    }
}
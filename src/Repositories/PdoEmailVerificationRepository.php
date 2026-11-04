<?php

namespace App\Repositories;

use App\Contracts\EmailVerificationRepository;
use Override;
use PDO;

final class PdoEmailVerificationRepository implements EmailVerificationRepository {

    public function __construct(private PDO $pdo) {}

    #[Override]
    public function storeToken(int $userId, string $hash, string $expires_at): bool
    {

        $stmt = $this->pdo->prepare(
            "INSERT INTO email_verification_tokens 
                (user_id, token_hash, expires_at, created_at)
            VALUES
                (:user_id, :insert_hash, :insert_expires, UTC_TIMESTAMP())
            ON DUPLICATE KEY UPDATE
                token_hash = :update_token,
                expires_at = :update_expires,
                created_at = UTC_TIMESTAMP()"
        );

        return $stmt->execute([
            'user_id' => $userId,
            'insert_hash' => $hash,
            'insert_expires' => $expires_at,
            'update_token' => $hash,
            'update_expires' => $expires_at
        ]);
        
    }

    #[Override]
    public function findToken(string $hash): ?object
    {
        return $this->fetchToken($hash, false);
    }

    #[Override]
    public function findForUpdate(string $hash): ?object
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
            "DELETE FROM email_verification_tokens WHERE user_id = :user_id"
        );

        $stmt->execute(['user_id' => $userId]);
    }

    private function fetchToken(string $hash, bool $lock): ?object 
    {
        $sql = "SELECT id, user_id, token_hash, expires_at
                FROM email_verification_tokens
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
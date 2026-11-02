<?php

namespace App\Repositories;

use App\Contracts\PasswordResetTokenRepository;
use Override;
use PDO;

final class PdoPasswordResetTokenRepository implements PasswordResetTokenRepository {
    public function __construct(private PDO $pdo) {}

    #[Override]
    public function storeResetHash(int $userId, string $hash, string $expiresAt): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO password_reset_tokens SET user_id = :user_id
            token_hash = :hash, expires_at = :expires"
        );

        $stmt->execute([
            'user_id' => $userId,
            'hash' => $hash,
            'expires' => $expiresAt
        ]);
    }

    #[Override]
    public function resetPassword(string $email, string $password): void 
    {

    }
}
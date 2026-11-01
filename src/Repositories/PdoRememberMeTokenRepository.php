<?php

namespace App\Repositories;

use App\Contracts\RememberTokenRepository;
use Override;
use PDO;
use RuntimeException;

final class PdoRememberMeTokenRepository implements RememberTokenRepository {

    public function __construct(private PDO $pdo) {} 

    #[Override]
    public function findToken(string $selector): ?object {
        $stmt = $this->pdo->prepare(
            "SELECT id, user_id, selector, validator_hash, expires_at
            FROM remember_tokens WHERE selector = :selector"
        );

        $stmt->execute(['selector' => $selector]);

        $data = $stmt->fetch(PDO::FETCH_OBJ);

        return $data === false ? null : $data;
    }

    #[Override]
    public function saveToken(int $userId, string $selector, string $validatorHash, string $expire): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO remember_tokens SET user_id = :user_id, selector = :selector,
            validator_hash = :validator, expires_at = :expire"
        );

        $stmt->execute([
            'user_id' => $userId,
            'selector' => $selector,
            'validator' => $validatorHash,
            'expire' => $expire
        ]);
    }

    #[Override]
    public function deleteToken(string $selector, string $validatorHash): void
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM remember_tokens WHERE selector = :selector AND validator_hash = :validator"
        );

        $stmt->execute([
            'selector' => $selector,
            'validator' => $validatorHash
        ]);
    }

    public function rotateToken(string $oldSelector, string $oldValidatorHash, string $newSelector, string $newValidatorHash): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE remember_tokens
             SET
                selector = :new_selector, validator_hash = :new_hash
             WHERE
                selector = :old_selector AND validator_hash = :old_hash 
                AND expires_at > :now   
            "
        );

        $stmt->execute([
            'new_selector' => $newSelector,
            'new_hash' => $newValidatorHash,
            'old_selector' => $oldSelector,
            'old_hash' => $oldValidatorHash,
            'now' => gmdate('Y-m-d H:i:s'),
        ]);

        return $stmt->rowCount() === 1;
    }
}
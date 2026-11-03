<?php

namespace App\Contracts;

interface PasswordResetTokenRepository {

    public function storeResetHash(int $userId, string $hash, string $expiresAt): bool;

    public function findToken(string $token): ?object;

    public function findTokenForUpdate(string $hash): ?object;

    public function deleteByUserId(int $userId): void;

}
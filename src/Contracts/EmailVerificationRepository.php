<?php

namespace App\Contracts;

interface EmailVerificationRepository {

    public function storeToken(int $userId, string $hash, string $expires_at): bool;

    public function findToken(string $hash): ?object;

    public function findForUpdate(string $hash): ?object;

    public function deleteByUserId(int $userId): void;

}
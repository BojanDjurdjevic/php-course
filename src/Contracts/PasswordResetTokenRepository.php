<?php

namespace App\Contracts;

interface PasswordResetTokenRepository {

    public function storeResetHash(int $userId, string $hash, string $expiresAt): bool;

}
<?php

namespace App\Contracts;

interface PasswordResetTokenRepository {

    public function storeResetHash(int $userId, string $hash, string $expiresAt): void;

    public function resetPassword(string $email, string $password): void;

}
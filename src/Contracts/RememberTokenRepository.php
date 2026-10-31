<?php

namespace App\Contracts;

interface RememberTokenRepository {

    public function findToken(string $selector): ?object;

    public function saveToken(int $userId, string $selector, string $validatorHash, string $expire): void;

    public function deleteToken(string $selector, string $validatorHash): void;

}
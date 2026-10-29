<?php

namespace App\Contracts;

use App\Models\User;

interface UserRepository {

    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function create(string $name, string $email, string $passwordHash): User;

    public function updatePasswordHash(int $userId, string $passwordHash): void;

    public function markEmailVerified(int $userId): void;

}
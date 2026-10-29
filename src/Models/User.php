<?php

namespace App\Models;

use App\Enums\UserStatus;
use DateTimeImmutable;

final class User {

    public function __construct(
        private int $id,
        private string $email,
        private string $passwordHash,
        private string $name,
        private ?string $city,
        private ?DateTimeImmutable $emailVerifiedAt,
        private UserStatus $status,
        private DateTimeImmutable $createdAt
    ) {}

    public function id(): int {
        return $this->id;
    }

    public function email(): string {
        return $this->email;
    }

    public function passwordHash(): string {
        return $this->passwordHash;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isEmailVerified(): bool {
        return $this->emailVerifiedAt !== null;
    }

    public function isActive(): bool {
        return $this->status === UserStatus::Active;
    }
}

?>
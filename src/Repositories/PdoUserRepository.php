<?php

namespace App\Repositories;

use App\Contracts\UserRepository;
use App\Enums\UserStatus;
use App\Models\User;
use DateTimeImmutable;
use Override;
use PDO;
use RuntimeException;

final class PdoUserRepository implements UserRepository {

    public function __construct(private PDO $pdo) {}

    #[Override]
    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, name, email, password_hash, city,
            email_verified_at, status, created_at FROM users WHERE id = :id"
        );

        $stmt->execute(['id' => $id]);

        $user = $stmt->fetch(PDO::FETCH_OBJ);

        return $user === false ? null : $this->hydrateUser($user);
        
    }

    #[Override]
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, name, email, password_hash, city,
            email_verified_at, status, created_at FROM users WHERE email = :email"
        );

        $stmt->execute(['email' => $email]);

        $user = $stmt->fetch(PDO::FETCH_OBJ);

        return $user === false ? null : $this->hydrateUser($user);
    }

    #[Override]
    public function create(string $name, string $email, string $passwordHash, ?string $city = null): User
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users SET name = :name, email = :email, password_hash = :password_hash, city = :city"
        );

        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'city' => $city
        ]);

        $userId = (int) $this->pdo->lastInsertId();

        return $this->findById($userId) ?? throw new RuntimeException('Created user not found');
    }

    #[Override]
    public function updatePasswordHash(int $userId, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET password_hash = :password_hash WHERE id = :id"
        );

        $stmt->execute([
            'id' => $userId,
            'password_hash' => $passwordHash
        ]);

        if($stmt->rowCount() !== 1) throw new RuntimeException('Password could not be updated.');
    }

    #[Override]
    public function markEmailVerified(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET email_verified_at = CURRENT_TIMESTAMP WHERE id = :id"
        );

        $now = new DateTimeImmutable();

        $stmt->execute(['id' => $userId]);

        if($stmt->rowCount() !== 1) throw new RuntimeException('Email could not be verified.');
    }

    private function hydrateUser(object $data): User
    {
        return new User(
            (int) $data->id, 
            $data->email, 
            $data->password_hash, 
            $data->name,
            $data->city, 
            $data->email_verified_at !== null ? new DateTimeImmutable($data->email_verified_at) : null, 
            UserStatus::from($data->status),
            new DateTimeImmutable($data->created_at),
        ); 
    }


}
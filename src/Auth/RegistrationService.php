<?php

namespace App\Auth;

use App\Contracts\UserRepository;
use App\Models\User;

final class RegistrationService {

    public function __construct(private UserRepository $users) {}

    public function register(
        string $name,
        string $email,
        string $password,
        ?string $city = null 
    ): User 
    {
        $normalizedEmail = strtolower($email);

        $user = $this->users->findByEmail($normalizedEmail);

        if($user !== null) throw new \DomainException('Email already registered.');

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        return $this->users->create($name, $normalizedEmail, $passwordHash, $city);
    }

}
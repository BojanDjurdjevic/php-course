<?php

namespace App\Auth;

use App\Contracts\UserRepository;
use App\Dto\UserRegisterData;
use App\Models\User;

final class RegistrationService {

    public function __construct(private UserRepository $users) {}

    public function register(
        UserRegisterData $data
    ): User 
    {
        $normalizedEmail = strtolower($data->email);

        $user = $this->users->findByEmail($normalizedEmail);

        if($user !== null) throw new \DomainException('Email already registered.');

        $passwordHash = password_hash($data->password, PASSWORD_DEFAULT);

        return $this->users->create($data->name, $normalizedEmail, $passwordHash, $data->city);
    }

}
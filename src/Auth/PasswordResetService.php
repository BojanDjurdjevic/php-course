<?php

namespace App\Auth;

use App\Contracts\PasswordResetTokenRepository;
use App\Contracts\UserRepository;

final class PasswordResetService {
    public function __construct(
        private PasswordResetTokenRepository $reset,
        private UserRepository $users
    ) {}

    public function issue(string $email): bool 
    {
        $email = mb_strtolower(trim($email));

        $user = $this->users->findByEmail($email);

        if($user === null) return false;

        $token = bin2hex(random_bytes(32));

        $hashed = hash('sha256', $token);

        $expiresAt = gmdate('Y-m-d H:i:s', time() + 1800);

        $stored = $this->reset->storeResetHash($user->id(), $hashed, $expiresAt);

        if(!$stored) return false;

        $baseUrl = rtrim($_ENV['APP_URL'], '/');

        $link = $baseUrl . '/index.php?route=reset-password&token=' . $token;

        //Log the link

        return true;
    }
}
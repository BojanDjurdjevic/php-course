<?php

namespace App\Auth;

use App\Contracts\PasswordResetTokenRepository;
use App\Contracts\UserRepository;
use App\Helpers\Logger;

final class PasswordResetService {
    public function __construct(
        private PasswordResetTokenRepository $reset,
        private UserRepository $users,
        private Logger $logger
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

        $isLocal = in_array(
            $_ENV['APP_ENV'] ?? '',
            ['local', 'development'],
            true
        );

        $logResetLinks = ($_ENV['LOG_RESET_LINKS'] ?? 'false') === 'true';

        if ($isLocal && $logResetLinks) {
            $this->logger->info('Password reset link: ' . $link);
        }

        return true;
    }
}
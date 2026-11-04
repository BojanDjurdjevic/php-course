<?php

namespace App\Auth;

use App\Contracts\EmailVerificationRepository;
use App\Contracts\UserRepository;
use App\Helpers\Logger;
use App\Models\User;
use PDO;

final class EmailVerificationService {
    public function __construct(
        private EmailVerificationRepository $verify,
        private UserRepository $users,
        private PDO $pdo,
        private Logger $logger
    ) {}

    public function issue(User $user): bool
    {
        if (!$user->isActive() || $user->isEmailVerified()) return false;

        $token = bin2hex(random_bytes(32));

        $hashed = hash('sha256', $token);

        $expiresAt = gmdate('Y-m-d H:i:s', time() + 60 * 60 * 24);

        $stored = $this->verify->storeToken($user->id(), $hashed, $expiresAt);

        if(!$stored) return false;

        $baseUrl = rtrim($_ENV['APP_URL'], '/');

        $link = $baseUrl . '/index.php?route=email-verify&token=' . $token;

        $isLocal = in_array(
            $_ENV['APP_ENV'] ?? '',
            ['local', 'development'],
            true
        );

        $logAuthLinks = ($_ENV['LOG_RESET_LINKS'] ?? 'false') === 'true';

        if ($isLocal && $logAuthLinks) {
            $this->logger->info('Email verify link: ' . $link);
        }

        return true;


    }

    public function validateToken(mixed $token): bool
    {
        $hash = $this->hashToken($token);

        if ($hash === null) return false;

        $data = $this->verify->findToken($hash);

        return $this->isUsable($data);
    }

    private function hashToken(mixed $token): ?string
    {
        if (
            !is_string($token) ||
            !preg_match('/\A[a-f0-9]{64}\z/', $token)
        ) return null;

        return hash('sha256', $token);
    }

    private function isUsable(?object $data): bool
    {
        if ($data === null) return false;

        if ($data->expires_at <= gmdate('Y-m-d H:i:s')) return false;

        $user = $this->users->findById((int) $data->user_id);

        return $user !== null && $user->isActive() && !$user->isEmailVerified();
    }
}
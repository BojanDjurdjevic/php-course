<?php

namespace App\Auth;

use App\Contracts\PasswordResetTokenRepository;
use App\Contracts\UserRepository;
use App\Helpers\Logger;
use PDO;
use Throwable;

final class PasswordResetService {
    public function __construct(
        private PasswordResetTokenRepository $reset,
        private UserRepository $users,
        private PDO $pdo,
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

    public function validateToken(string $token): bool 
    {
        if(!is_string($token)) return false;

        if(!preg_match('/\A[a-f0-9]{64}\z/', $token)) return false;

        $hashed = hash('sha256', $token);

        $data = $this->reset->findToken($hashed);

        if($data === null) return false;

        if($data->expires_at < gmdate('Y-m-d H:i:s')) return false;

        $user = $this->users->findById($data->user_id);

        if($user === null || !$user->isActive()) return false;

        return true;
    }

    public function finalReset($token, $userId, $passwordHash): void
    {
        $this->pdo->beginTransaction();

        try {

            

        } catch(Throwable $e) {

        }
    }
}
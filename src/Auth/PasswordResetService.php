<?php

namespace App\Auth;

use App\Contracts\PasswordResetTokenRepository;
use App\Contracts\RememberTokenRepository;
use App\Contracts\UserRepository;
use App\Helpers\Logger;
use PDO;

final class PasswordResetService {
    public function __construct(
        private PasswordResetTokenRepository $reset,
        private UserRepository $users,
        private RememberTokenRepository $remember,
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

    public function validateToken(mixed $token): bool
    {
        $hash = $this->hashToken($token);

        if ($hash === null) return false;

        $data = $this->reset->findToken($hash);

        return $this->isUsable($data);
    }

    public function finalReset(mixed $token, string $passwordHash): bool
    {
        $hash = $this->hashToken($token);

        if ($hash === null) {
            return false;
        }

        $this->pdo->beginTransaction();

        try {
            // 1. Pronađi i zaključaj token
            $data = $this->reset->findTokenForUpdate($hash);

            // 2. Proveri token unutar transakcije
            if (!$this->isUsable($data)) {
                $this->pdo->rollBack();
                return false;
            }

            $userId = (int) $data->user_id;

            // 3. Promeni lozinku korisnika
            $this->users->updatePasswordHash(
                $userId,
                $passwordHash
            );

            // 4. Increment auth version
            $this->users->incrementAuthVersion($userId);

            // 5. Delete remember tokens
            $this->remember->deleteByUserId($userId);

            // 6. Obriši reset token
            $this->reset->deleteByUserId($userId);

            // 7. Potvrdi sve promene
            $this->pdo->commit();

            return true;

        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
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

        return $user !== null && $user->isActive();
    }
}
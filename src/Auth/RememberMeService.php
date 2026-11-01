<?php

namespace App\Auth;

use App\Contracts\RememberTokenRepository;
use App\Contracts\UserRepository;
use App\Models\User;
use stdClass;

final class RememberMeService {

    public function __construct(
        private UserRepository $users,
        private RememberTokenRepository $remember,
        private AuthService $auth
    ) {}

    public function issue(User $user): void {
        $data = $this->generateTokenData();

        $this->remember->saveToken($user->id(), $data->selector, $data->hashed, $data->expiresAt);

        $this->setCookie($data->selector,  $data->validator, $data->expires);

    }

    public function restore(): bool
    {
        // Do not touch user if there is already active session
        if($this->auth->check()) return false;

        $data = $this->cookieIsValid();

        if($data === null) return false;

        $user = $this->users->findById((int) $data->user_id);

        if($user === null || !$user->isActive()) {
            $this->remember->deleteToken($data->selector, $data->validator_hash);
            
            $this->clearCookie();
            
            return false;
        } 

        if(!$this->rotate($data)) return false;

        // Authenticated session
        $this->auth->login($user);

        return true;
    }

    public function revoke(): void
    {
        $data = $this->cookieIsValid();

        if($data !== null) $this->remember->deleteToken($data->selector, $data->validator_hash);

        $this->clearCookie();
    }

    private function rotate(object $oldData): bool
    {
        $expiration = \DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i:s',
            $oldData->expires_at,
            new \DateTimeZone('UTC')
        );

        if (
            $expiration === false ||
            $expiration->getTimestamp() <= time()
        ) {
            return false;
        }

        $newData = $this->generateTokenData(
            $expiration->getTimestamp()
        );

        if (!$this->remember->rotateToken(
            $oldData->selector,
            $oldData->validator_hash,
            $newData->selector,
            $newData->hashed
        )) {
            return false;
        }

        $this->setCookie(
            $newData->selector,
            $newData->validator,
            $newData->expires
        );

        return true;
    }

    // PRIVATE Helpers

    private function generateTokenData(?int $expires = null): stdClass
    {
        $selector = bin2hex(random_bytes(9));
        $validator = bin2hex(random_bytes(32));
        $hashedValidator = hash('sha256', $validator);

        $expires ??= time() + 60 * 60 * 24 * 30;

        $data = new stdClass();

        $data->selector = $selector;
        $data->validator = $validator;
        $data->hashed = $hashedValidator;
        $data->expires = $expires;
        $data->expiresAt = gmdate('Y-m-d H:i:s', $expires);

        return $data;
    }

    private function cookieIsValid(): ?object
    {
        $cookie = $_COOKIE['remember_me'] ?? null;

        if(!is_string($cookie)) return null;

        $parts = explode(':', $cookie);

        if(count($parts) !== 2) {
            $this->clearCookie();
            return null;
        }

        [$selector, $validator] = $parts;

        if (
            !preg_match('/\A[a-f0-9]{18}\z/', $selector) ||
            !preg_match('/\A[a-f0-9]{64}\z/', $validator)
        ) {
            $this->clearCookie();
            return null;
        }

        $data = $this->remember->findToken($selector);

        if($data === null) {
            //$this->clearCookie();
            return null;
        } 

        // Token expired?
        if($data->expires_at <= gmdate('Y-m-d H:i:s')) {
            $this->clearCookie();
            return null;
        } 

        // Check validator
        $validatorHash = hash('sha256', $validator);

        if(!hash_equals($data->validator_hash, $validatorHash)) {
            //$this->clearCookie();
            return null;
        }

        return $data;
    }

    private function setCookie(string $selector, string $validator, int $expire) 
    {
        setcookie(
            'remember_me', 
            $selector . ':' . $validator,
            [
                'expires' => $expire,
                'path' => '/',
                'httponly' => true,
                'secure' => ($_ENV['APP_ENV'] ?? 'development') === 'production',
                'samesite' => 'Lax'
            ]
        );
    }

    private function clearCookie(): void
    {
        setcookie('remember_me', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'secure' => ($_ENV['APP_ENV'] ?? 'local') === 'production',
            'samesite' => 'Lax'
        ]);

        unset($_COOKIE['remember_me']);
    }

}
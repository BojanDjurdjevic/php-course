<?php

namespace App\Auth;

use App\Contracts\RememberTokenRepository;
use App\Contracts\UserRepository;
use App\Models\User;

final class RememberMeService {

    public function __construct(
        private UserRepository $users,
        private RememberTokenRepository $remember,
        private AuthService $auth
    ) {}

    public function issue(User $user): void {
        $selector = bin2hex(random_bytes(9));

        $validator = bin2hex(random_bytes(32));

        $hashed_validator = hash('sha256', $validator);

        $expire = time() + 60 * 60 * 24 *30;

        $expiresAt = gmdate('Y-m-d H:i:s', $expire);

        $this->remember->saveToken($user->id(), $selector, $hashed_validator, $expiresAt);

        $isProduction = ($_ENV['APP_ENV'] ?? 'development') === 'production';

        setcookie(
            'remember_me', 
            $selector . ':' . $validator,
            [
                'expires' => $expire,
                'path' => '/',
                'httponly' => true,
                'secure' => $isProduction,
                'samesite' => 'Lax'
            ]
        );

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
            $this->clearCookie();
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
            $this->clearCookie();
            return null;
        }

        return $data;
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
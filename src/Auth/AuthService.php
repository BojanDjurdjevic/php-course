<?php

namespace App\Auth;

use App\Contracts\UserRepository;
use App\Dto\UserLoginData;
use App\Enums\LoginResult;
use App\Models\User;
use App\Session\Session;
use DomainException;

final class AuthService {

    private ?User $currentUser = null;

    private bool $userResolved = false;

    public function __construct(
        private UserRepository $users,
        private Session $session
    ) {}

    public function id(): ?int 
    {
        $id = $this->session->get('user_id');

        return $id === null ? null : (int) $id;
    }

    public function user(): ?User
    {
        if($this->userResolved) return $this->currentUser;

        $this->userResolved = true;

        $id = $this->id();

        if($id === null) return null;

        $user = $this->users->findById($id);

        if($user === null || !$user->isActive() || !$user->isEmailVerified()) {
            $this->session->forget('user_id');
            $this->session->forget('auth_version');

            return null;
        }

        if($user->authVersion() !== $this->session->get('auth_version')) {
            $this->session->forget('user_id');
            $this->session->forget('auth_version');

            return null;
        }

        $this->currentUser = $user;

        return $this->currentUser;
    }

    public function check(): bool 
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function attempt(UserLoginData $data): LoginResult 
    {
        $normalizedEmail = mb_strtolower(trim($data->email));

        $password = $data->password;

        $user = $this->users->findByEmail($normalizedEmail);

        if($user === null || !$user->isActive()) {
            return LoginResult::INVALID_CREDENTIALS;
        }

        if(!password_verify($password, $user->passwordHash())) {
            return LoginResult::INVALID_CREDENTIALS;
        }

        if(!$user->isEmailVerified()) {
            return LoginResult::EMAIL_NOT_VERIFIED;
        }

        if(password_needs_rehash($user->passwordHash(), PASSWORD_DEFAULT)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            if(!$this->users->rehashIfUnchanged($user->id(), $user->passwordHash(), $user->authVersion(), $hashed)) {
                return LoginResult::INVALID_CREDENTIALS;
            }
        }

        $this->login($user);

        return LoginResult::SUCCESS;

    }

    public function login(User $user): void
    {
        $this->session->regenerate();

        $this->session->put('user_id', $user->id());
        $this->session->put('auth_version', $user->authVersion());

        $this->currentUser = $user;
        $this->userResolved = true;
    }

    public function logout(): void
    {
        //$this->session->forget('user_id');
        $this->session->destroy();

        $this->currentUser = null;
        $this->userResolved = true;
    }

}
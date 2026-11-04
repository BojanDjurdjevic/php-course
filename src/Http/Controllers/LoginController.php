<?php

namespace App\Http\Controllers;

use App\Auth\AuthService;
use App\Auth\RememberMeService;
use App\Enums\LoginResult;
use App\Helpers\Validator;
use App\Http\Requests\UserLoginRequest;
use App\Session\Session;
use Throwable;

final class LoginController {
    public function __construct(
        private AuthService $auth,
        private RememberMeService $remember,
        private Session $session
    ) {}

    public function login(): void
    {
        $expected = $this->session->get('csrf_token');
        $received = $_POST['_token'] ?? null;

        if(!is_string($expected) || !is_string($received) || !hash_equals($expected, $received)) {
            //$this->session->put('page', 'loginpage');
            $page = 'loginpage';

            http_response_code(403);
            require __DIR__ . '/../../../views/errors/403.php';
            exit;
        }

        $request = new UserLoginRequest(
            new Validator,
            $_POST['email'] ?? null,
            $_POST['password'] ?? null,
        );

        $validated = $request->validate();

        if(is_array($validated)) {
            $this->session->flashSet('errors', $validated);

            header('location: index.php?route=loginpage', true, 303);
            exit;
        }

        $result = $this->auth->attempt($validated);

        if ($result === LoginResult::EMAIL_NOT_VERIFIED) {
            $this->session->flashSet('errors', [
                'login' => 'Please verify your email before logging in.'
            ]);

            header('Location: index.php?route=login', true, 303);
            exit;
        }

        if ($result !== LoginResult::SUCCESS) {
            $this->session->flashSet('errors', [
                'login' => 'Invalid credentials.'
            ]);

            header('Location: index.php?route=login', true, 303);
            exit;
        }

        $this->remember->revoke();

        $user = $this->auth->user();
        if($user !== null && ($_POST['remember_me'] ?? null) === "1") $this->remember->issue($user);

        header('location: index.php?route=dashboard', true, 303);
        exit;
    }

    public function logout(): void
    {
        $expected = $this->session->get('csrf_token');
        $received = $_POST['_token'] ?? null;

        if(!is_string($expected) || !is_string($received) || !hash_equals($expected, $received)) {
            //$this->session->put('page', 'dashboard');
            $page = 'dashboard';

            http_response_code(403);
            require __DIR__ . '/../../../views/errors/403.php';
            exit;
        }

        $this->remember->revoke();
        $this->auth->logout();

        header('Location: index.php?route=loginpage', true, 303);
        exit;
    }
}
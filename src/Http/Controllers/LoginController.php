<?php

namespace App\Http\Controllers;

use App\Auth\AuthService;
use App\Helpers\Validator;
use App\Http\Requests\UserLoginRequest;
use App\Session\Session;
use Throwable;

final class LoginController {
    public function __construct(
        private AuthService $auth,
        private Session $session
    ) {}

    public function login(): void
    {
        $expected = $this->session->get('csrf-token');
        $received = $_POST['_token'] ?? null;

        if(!is_string($expected) || !is_string($received) || !hash_equals($expected, $received)) {
            $this->session->put('page', 'login');

            http_response_code(403);
            require __DIR__ . '/../../../views/errors/403.php';
            exit;
        }

        $request = new UserLoginRequest(
            new Validator,
            $_POST['email'] ?? null,
            $_POST['password']
        );

        $validated = $request->validate();

        if(is_array($validated)) {
            $this->session->flashSet('errors', $validated);

            header('location: index.php?route=login', true, 303);
            exit;
        }

        if (!$this->auth->attempt($validated)) {
            $this->session->flashSet('errors', [
                'login' => 'Invalid credentials.'
            ]);

            header('Location: index.php?route=login', true, 303);
            exit;
        }

        header('location: index.php?route=dashboard', true, 303);
        exit;
    }

    public function logout(): void
    {

    }
}
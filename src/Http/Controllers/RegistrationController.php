<?php

namespace App\Http\Controllers;

use App\Auth\EmailVerificationService;
use App\Auth\RegistrationService;
use App\Helpers\Validator;
use App\Http\Requests\UserRegisterRequest;
use App\Security\RateLimiter;
use App\Session\Session;
use DomainException;

final class RegistrationController {
    public function __construct(
            private RegistrationService $service,
            private EmailVerificationService $verify,
            private Session $session,
            private RateLimiter $limiter
        ) {}

    public function register(): void
    {
        $expected = $this->session->get('csrf_token');
        $received = $_POST['_token'] ?? null;

        if (
            !is_string($expected) ||
            !is_string($received) ||
            !hash_equals($expected, $received)
        ) {
            $page = 'register';

            http_response_code(403);
            require __DIR__ . '/../../../views/errors/403.php';
            exit;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $ipAllowed = $this->limiter->allowPerHour(
            'register_ip',
            $ip,
            10
        );

        if(!$ipAllowed) {
            $this->session->flashSet('errors', [
                'register' => 'Too many registration attempts. Please try again later.'
            ]);

            header('Location: index.php?route=register', true, 303);
            exit;
        }

        $request = new UserRegisterRequest(
            new Validator(), 
            $_POST['name'] ?? null, 
            $_POST['email'] ?? null,
            $_POST['password'] ?? null,
            $_POST['password_confirmation'] ?? null,
            $_POST['city'] ?? null
        );

        $validated = $request->validated();

        if(is_array($validated)) {
            $this->session->flashSet('errors', $validated);

            header('location: index.php?route=register', true, 303);
            exit;
        }

        $emailAllowed = $this->limiter->allowPerHour(
            'register_email',
            $validated->email,
            3
        );

        if(!$emailAllowed) {
            $this->session->flashSet('errors', [
                'register' => 'Too many registration attempts. Please try again later.'
            ]);

            header('Location: index.php?route=register', true, 303);
            exit;
        }

        try {
            $user = $this->service->register($validated);
        } catch(DomainException $e) {
            $this->session->flashSet('errors', [
                'email' => 'Email address already registered.'
            ]);

            header('location: index.php?route=register', true, 303);
            exit;
        }

        $emailVerify = $this->verify->issue($user);

        if(!$emailVerify) {
            $this->session->flashSet('success', 'Registration completed. Please request a new verification link.');

            header('Location: index.php?route=resend-verification', true, 303);
            exit;
        }

        $this->session->flashSet('success', 'Registration completed. Please verify your email before logging in.');
        
        header('location: index.php?route=loginpage', true, 303);
        exit;
        
    }
}
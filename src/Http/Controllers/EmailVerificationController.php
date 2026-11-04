<?php

namespace App\Http\Controllers;

use App\Auth\EmailVerificationService;
use App\Security\RateLimiter;
use App\Session\Session;

final class EmailVerificationController {
    public function __construct(
        private EmailVerificationService $verify,
        private Session $session,
        private RateLimiter $limiter
    ) {}

    public function emailVerify(): void
    {
        $expected = $this->session->get('csrf_token');
        $received = $_POST['_token'] ?? null;

        if(!is_string($expected) || !is_string($received) || !hash_equals($expected, $received)) {
            $this->session->put('page', 'loginpage');

            http_response_code(403);
            require __DIR__ . '/../../../views/errors/403.php';
            exit;
        }

        $token = $_POST['email_verify_token'] ?? null;

        if(!is_string($token)) {
            http_response_code(400);
            require __DIR__ . '/../../../views/errors/invalid-reset-token.php';
            exit;
        }

        if(!$this->verify->verify($token)) {
            http_response_code(400);
            require __DIR__ . '/../../../views/errors/invalid-reset-token.php';
            exit;
        }

        $this->session->flashSet(
            'success',
            'Email has been successfully verified.'
        );

        header('Location: index.php?route=login', true, 303);
        exit;
    }

    public function sendAgain(): void
    {
        $expected = $this->session->get('csrf_token');
        $received = $_POST['_token'] ?? null;

        if(!is_string($expected) || !is_string($received) || !hash_equals($expected, $received)) {
            $this->session->put('page', 'forgot-password');

            http_response_code(403);
            require __DIR__ . '/../../../views/errors/403.php';
            exit;
        }

        $email = $_POST['email'] ?? null;

        if (
            !is_string($email) ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            // Flash validation error + redirect
            return;
        }

        $email = mb_strtolower(trim($email));
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $emailAllowed = $this->limiter->allowPerHour(
            'verification_email',
            $email,
            3
        );

        $ipAllowed = $this->limiter->allowPerHour(
            'verification_ip',
            $ip,
            20
        );

        if ($emailAllowed && $ipAllowed) {
            $this->verify->resend($email);
        }

        $this->session->flashSet(
            'success',
            'If this account exists and is not verified, verification instructions will be provided.'
        );
    }
}
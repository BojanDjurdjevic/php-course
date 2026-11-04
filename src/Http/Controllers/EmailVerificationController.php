<?php

namespace App\Http\Controllers;

use App\Auth\EmailVerificationService;
use App\Security\RateLimiter;
use App\Session\Session;

final class EmailVerificationController {
    public function __construct(
        private EmailVerificationService $verify,
        private Session $session,
        private RateLimiter  $limiter
    ) {}

    public function emailVerify(): void
    {
        $expected = $this->session->get('csrf_token');
        $received = $_POST['_token'] ?? null;

        if(!is_string($expected) || !is_string($received) || !hash_equals($expected, $received)) {
            //$this->session->put('page', 'resend-verification');
            $page = 'resend-verification';

            http_response_code(403);
            require __DIR__ . '/../../../views/errors/403.php';
            exit;
        }

        $token = $_POST['email_verify_token'] ?? null;

        if(!is_string($token)) {
            http_response_code(400);
            require __DIR__ . '/../../../views/errors/invalid-verification-token.php';
            exit;
        }

        if(!$this->verify->verify($token)) {
            http_response_code(400);
            require __DIR__ . '/../../../views/errors/invalid-verification-token.php';
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
            //$this->session->put('page', 'resend-verification');
            $page = 'resend-verification';

            http_response_code(403);
            require __DIR__ . '/../../../views/errors/403.php';
            exit;
        }

        $email = $_POST['email'] ?? null;

        if(!is_string($email)) $email = mb_strtolower(trim($email));

        if (
            !is_string($email) ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            $this->session->flashSet('errors', [
                'email' => 'Please enter a valid email address.'
            ]);

            header('Location: index.php?route=resend-verification', true, 303);
            exit;
        }

        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $ipAllowed = $this->limiter->allowPerHour(
            'verification_ip',
            $ip,
            20
        );

        if ($ipAllowed) {
            $emailAllowed = $this->limiter->allowPerHour(
                'verification_email',
                $email,
                3
            );

            if ($emailAllowed) {
                $this->verify->resend($email);
            }
        }

        $this->session->flashSet(
            'success',
            'If the account is eligible, verification instructions will be provided.'
        );

        header('Location: index.php?route=resend-verification', true, 303);
        exit;
    }
}
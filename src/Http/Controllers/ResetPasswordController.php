<?php

namespace App\Http\Controllers;

use App\Auth\PasswordResetService;
use App\Helpers\Validator;
use App\Http\Requests\ForgotPasswordRequest;
use App\Security\RateLimiter;
use App\Session\Session;

final class ResetPasswordController {

    public function __construct(
        private Session $session,
        private PasswordResetService $reset,
        private RateLimiter $limiter
    ) {}

    public function sendLink(): void 
    {
        $expected = $this->session->get('csrf_token');
        $received = $_POST['_token'] ?? null;

        if(!is_string($expected) || !is_string($received) || !hash_equals($expected, $received)) {
            $this->session->put('page', 'forgot-password');

            http_response_code(403);
            require __DIR__ . '/../../../views/errors/403.php';
            exit;
        }

        $request = new ForgotPasswordRequest(new Validator);

        $validated = $request->validated($_POST['email'] ?? null, null);

        $email = mb_strtolower(trim($validated->email));

        if(is_array($validated)) {
            $this->session->flashSet('errors', $validated);

            header('location: index.php?route=forgot-password', true, 303);
            exit;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $emailAllowed = $this->limiter->allowPerHour(
            'forgot_email',
            $email,
            3
        );

        $ipAllowed = $this->limiter->allowPerHour(
            'forgot_ip',
            $ip,
            20
        );

        if ($emailAllowed && $ipAllowed) {
            $issued = $this->reset->issue($email);
        }

        if(!$issued) {
            //Log activity
        }

        $this->session->flashSet('success', 'If an account with this email exists, reset instructions will be provided.');

        header('location: index.php?route=forgot-password', true, 303);
        exit;
        
    }
}
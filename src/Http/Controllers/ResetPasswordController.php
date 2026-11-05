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
            //$this->session->put('page', 'forgot-password');
            $page = 'forgot-password';

            http_response_code(403);
            require __DIR__ . '/../../../views/errors/403.php';
            exit;
        }

        $request = new ForgotPasswordRequest(new Validator);

        $validated = $request->validated($_POST['email'] ?? null, null);

        if(is_array($validated)) {
            $this->session->flashSet('errors', $validated);

            header('location: index.php?route=forgot-password', true, 303);
            exit;
        }

        $email = mb_strtolower(trim($validated->email));

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

        $this->session->flashSet('success', 'If an account with this email exists, reset instructions will be provided.');

        header('location: index.php?route=forgot-password', true, 303);
        exit;
        
    }

    public function resetPassword(): void 
    {
        // CSRF
        $expected = $this->session->get('csrf_token');
        $received = $_POST['_token'] ?? null;

        if (
            !is_string($expected) ||
            !is_string($received) ||
            !hash_equals($expected, $received)
        ) {
            $page = 'forgot-password';
            
            http_response_code(403);
            require __DIR__ . '/../../../views/errors/403.php';
            exit;
        }

        $token = $_POST['token'] ?? null;

        if (!is_string($token)) {
            http_response_code(400);
            require __DIR__ . '/../../../views/errors/invalid-reset-token.php';
            exit;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $ipAllowed = $this->limiter->allowPerHour(
            'reset_submit_ip',
            $ip,
            9
        );

        if(!$ipAllowed) {
            $this->session->flashSet(
                'errors',
                ['reset' => 'Too many attempts, pls try again later.']
            );

            header(
                'Location: index.php?route=reset-password&token='
                . rawurlencode($token),
                true,
                303
            );
            exit;
        }

        if (!$this->reset->validateToken($token)) {
            http_response_code(400);
            require __DIR__ . '/../../../views/errors/invalid-reset-token.php';
            exit;
        }

        // REQUEST validate
        $password = $_POST['password'] ?? null;
        $passwordConfirm = $_POST['password_confirmation'] ?? null;

        $validator = new Validator();

        $confirm = $validator->validatePasswordConfirm($password, 'password_confirmation', $passwordConfirm);
        $password = $validator->validatePassword($password, 'password');

        if(!$confirm || $password === null) {
            $this->session->flashSet(
                'errors', $validator->errors()
            );

            header(
                'Location: index.php?route=reset-password&token='
                . rawurlencode($token),
                true,
                303
            );
            exit;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $reset = $this->reset->finalReset($token, $passwordHash);

        if (!$reset) {
            http_response_code(400);
            require __DIR__ . '/../../../views/errors/invalid-reset-token.php';
            exit;
        }

        $this->session->flashSet(
            'success',
            'Password has been reset successfully.'
        );

        header('Location: index.php?route=login', true, 303);
        exit;
    }

}
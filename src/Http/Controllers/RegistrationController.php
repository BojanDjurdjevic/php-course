<?php

namespace App\Http\Controllers;

use App\Auth\RegistrationService;
use App\Helpers\Validator;
use App\Http\Requests\UserRegisterRequest;
use App\Session\Session;
use DomainException;

final class RegistrationController {
    public function __construct(
            private RegistrationService $service,
            private Session $session
        ) {}

    public function register(): void
    {
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

        try {
            $this->service->register($validated);
        } catch(DomainException $e) {
            $this->session->flashSet('errors', [
                'email' => 'Email address already registered.'
            ]);

            header('location: index.php?route=register', true, 303);
            exit;
        }
        
        header('location: index.php?route=loginpage', true, 303);
        exit;
        
    }
}
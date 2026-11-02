<?php

namespace App\Http\Requests;

use App\Contracts\PasswordRequest;
use App\Dto\PasswordResetData;
use App\Helpers\Validator;
use Override;

final class ForgotPasswordRequest implements PasswordRequest {

    public function __construct(private Validator $validator) {}

    #[Override]
    public function validated(mixed $email, mixed $password = null): array | PasswordResetData
    {
        $email = $this->validator->validateEmail($email, 'email');

        if($password !== null) $password = $this->validator->validatePassword($password, 'password');

        if($this->validator->errors() !== []) {
            $errors = $this->validator->errors();
            $this->validator->resetErrors();
            return $errors;
        }

        return new PasswordResetData($email, $password);
    }
}
<?php

namespace App\Http\Requests;

use App\Dto\UserLoginData;
use App\Helpers\Validator;

final class UserLoginRequest {

    public function __construct(
        private Validator $validator,
        public mixed $email,
        public mixed $password 
    ) {}

    public function validate(): array | UserLoginData
    {
        $email = $this->validator->validateEmail($this->email, 'email');
        $password = $this->validator->validatePassword($this->password, 'password');

        if($this->validator->errors() !== []) {
            $errors = $this->validator->errors();
            $this->validator->resetErrors();
            return $errors;
        }

        return new UserLoginData($email, $password);
    }

}
<?php

namespace App\Http\Requests;

use App\Dto\UserRegisterData;
use App\Helpers\Validator;
use Override;

class UserRegisterRequest implements Request {

    public function __construct(

        private Validator $validator,
        public mixed $name,
        public mixed $email,
        public mixed $password,
        public mixed $confirm,
        public mixed $city = null,

        
    ) {}

    #[Override]
    public function validated(): array | UserRegisterData
    {
        $name = $this->validator->validateRequiredString($this->name, 'name', 2, 55);
        $email = $this->validator->validateEmail($this->email, 'email');
        $password = $this->validator->validatePassword($this->password, 'password');
        $confirmation = $this->validator->validatePasswordConfirm($password, 'password_confirmation', $this->confirm);
        $city = $this->validator->validateNullableString($this->city, 'city');

        if($this->validator->errors() !== []) {
            $errors = $this->validator->errors();
            $this->validator->resetErrors();
            return $errors;
        } 

        return new UserRegisterData($name, $email, $password, $city);
    }
}
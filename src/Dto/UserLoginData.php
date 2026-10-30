<?php

namespace App\Dto;

final readonly class UserLoginData {

    public function __construct(
        public string $email,
        public string $password 
    ) {}

}
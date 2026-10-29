<?php

namespace App\Dto;

final readonly class UserRegisterData {

    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $city = null,
    ) {}

    

}
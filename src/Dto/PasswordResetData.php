<?php

namespace App\Dto;

final readonly class PasswordResetData {

    public function __construct(
        public string $email,
        public ?string $password = null
    ) {}

}
<?php

namespace App\Contracts;

use App\Dto\PasswordResetData;

interface PasswordRequest {

    public function validated(mixed $email, mixed $password = null): array | PasswordResetData;

}
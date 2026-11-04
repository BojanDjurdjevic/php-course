<?php

namespace App\Enums;

enum LoginResult
{
    case SUCCESS;
    case INVALID_CREDENTIALS;
    case EMAIL_NOT_VERIFIED;
}
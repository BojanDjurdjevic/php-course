<?php

namespace App\Http\Requests;

use App\Dto\UserRegisterData;

interface Request {

    public function validated(): array | UserRegisterData;

}
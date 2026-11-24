<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Fake DB stub
|--------------------------------------------------------------------------
|
| MySQL i PDO još nisu tema ovog modula.
| Ovaj niz samo glumi red koji bi kasnije stigao iz baze.
|
| Test login:
| email:    bojan@example.com
| password: Secret123!
|
*/

function findUserByEmail(string $email): ?array
{
    $user = [
        'id' => 15,
        'email' => 'bojan@example.com',
        'name' => 'Bojan',
        'password_hash' => '$2y$12$pgWBE61SIL37yb9VDERWquH14a1HxRQDwma0WR7Psa6YDK5lYo7aq',
    ];

    return hash_equals($user['email'], $email)
        ? $user
        : null;
}

<?php

declare(strict_types=1);

require_once __DIR__ . '/session_bootstrap.php';
require_once __DIR__ . '/flash_helpers.php';
require_once __DIR__ . '/fake_user_store.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: signin_view.php', true, 303);
    exit;
}

$email = $_POST['email'] ?? null;
$password = $_POST['password'] ?? null;

$old = [
    'email' => is_string($email)
        ? trim($email)
        : '',
];

$errors = [];

if (!is_string($email)) {
    $errors['email'] = 'Email je obavezan.';
} else {
    $email = trim($email);

    if ($email === '') {
        $errors['email'] = 'Email je obavezan.';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Email nije validan.';
    }
}

if (!is_string($password) || $password === '') {
    $errors['password'] = 'Password je obavezan.';
}

if ($errors !== []) {
    flashSet('errors', $errors);
    flashSet('old', $old);

    header('Location: signin_view.php', true, 303);
    exit;
}

/*
|--------------------------------------------------------------------------
| Ovo kasnije postaje PDO query
|--------------------------------------------------------------------------
|
| SELECT id, email, name, password_hash
| FROM users
| WHERE email = :email
| LIMIT 1
|
*/
$user = findUserByEmail($email);

if (
    $user === null
    || !password_verify($password, $user['password_hash'])
) {
    flashSet('errors', [
        'credentials' => 'Email ili password nisu ispravni.',
    ]);

    flashSet('old', $old);

    header('Location: signin_view.php', true, 303);
    exit;
}

/*
|--------------------------------------------------------------------------
| Authentication boundary
|--------------------------------------------------------------------------
|
| Identitet je uspešno potvrđen.
| Menjamo session ID pre nego što session postane authenticated.
|
*/
session_regenerate_id(true);

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['last_activity'] = time();

flashSet(
    'message',
    'Dobrodošao, ' . $user['name'] . '!'
);

header('Location: member_area.php', true, 303);
exit;

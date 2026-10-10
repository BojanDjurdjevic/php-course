<?php

require_once __DIR__ . '/../../includes/session.php';

include_once __DIR__ . '/../../validation/errors/validator.php';


$email = $_POST['email'] ?? null;
$password = $_POST['password'] ?? null;
$remember = $_POST['remember'] ?? null;


$old = [
    'email' => is_string($email) ? $email : '',
];

$errors = [];

$email = validateEmail($email, 'email', $errors);
$password = validateRequiredString($password, 'Password', $errors, 8, 25);

if($errors !== []) {
    
    $_SESSION['errors'] = $errors;

    $_SESSION['old'] = $old;
 
} else {
    
    $validated = [
        'email' => $email,
        'password' => $password,
    ];

    // Prvo tra-ćžimo user-a
    $sql = "SELECT * FROM users WHERE email = :email AND deleted = 0";

    // Recimo da je ovo nazad iz baze:
    $user = [
        'id' => 15,
        'email' => $email,
        'password' => $password,
        'name' => 'Bojan',
    ]; 

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    if($user) {
        if(password_verify($user['password'], $hashed)) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];

            if($remember) {
                $token = bin2hex(random_bytes(16));

                $hashed_token = password_hash($token, PASSWORD_DEFAULT);

                // Upis tokena u bazu - pretpostavljamo
                $sql_token = "INSERT INTO users SET remember_token = :remember_token WHERE user_id = :user_id";

                setcookie('remember_token', $token, [
                    'expires' => time() + (86400 * 10),
                    'path' => '/',
                    'secure' => false,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }

            $_SESSION['success'] = "Welcome " . $user['name'];

            header('Location: index.php', true, 303);
            exit;
        }
    }

    $errors['credentials'] = 'Invalid email or password.';
    header('Location: index.php', true, 303);
    exit;
    
}



?>
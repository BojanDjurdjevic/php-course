<?php

session_start();

$method = $_SERVER['REQUEST_METHOD'];

$name = $_POST['name'] ?? null;
$email = $_POST['email'] ?? null;

if($method !== 'POST') {
    header('Allow: POST');
    http_response_code(405);

    exit('Method Not Allowed');
}

$_SESSION['old'] = [
    'name' => is_string($name) ? $name : '',
    'email' => is_string($email) ? $email : '',
];

if(!is_string($name) || trim($name) === '') {
    $_SESSION['errors'] = 'Name is required and must be a string!';    

    header('Location: create.php', true, 303);
    exit;
}

$name = trim($name);

if(!is_string($email) || trim($email) === '') {
    $_SESSION['errors'] = 'Email is required';

    header('Location: create.php', true, 303);
    exit;
}

$email = trim($email);

if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['errors'] = 'Email is invalid. Please enter a valid email.';

    header('Location: create.php', true, 303);
    exit;
}

// Upis u bazu...

$_SESSION['flash'] = 'User successfully created!';

unset($_SESSION['old']);

header('Location: create.php', true, 303);
exit;

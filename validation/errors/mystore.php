<?php

session_start();

include_once __DIR__ . '/validator.php';

$name = $_POST['name'] ?? null;
$email = $_POST['email'] ?? null;
$age = $_POST['age'] ?? null;

$phoneExists = array_key_exists('phone', $_POST);

$phoneRaw = $phoneExists ? $_POST['phone'] : null;

$old = [
    'name' => is_string($name) ? $name : '',
    'email' => is_string($email) ? $email : '',
    'age' => is_string($age) ? $age : '',
    'phone' => is_string($phoneRaw) ? $phoneRaw : '',
];

$errors = [];


$name = validateRequiredString($name, 'name', $errors, 2, 100);

$email = validateEmail($email, 'email', $errors);

$age = validateInteger($age, 'age', $errors, 18, 100);

$phone = $phoneExists ? validatePhone($phoneRaw, 'phone', $errors) : null;


if($errors !== []) {
    
    $_SESSION['errors'] = $errors;

    $_SESSION['old'] = $old;
 
} else {
    
    $validated = [
        'name' => $name,
        'email' => $email,
        'age' => $age,
        'phone' => $phone
    ];

    // Upis: $userRepository->create($validated);

    $_SESSION['success'] = 'The user is stored successfully!';
}
/*
$errors = [];

$checkIn  = validateDate($_POST['check_in'] ?? null, 'check_in', $errors);
$checkOut = validateDate($_POST['check_out'] ?? null, 'check_out', $errors);

validateNotInPast($checkIn, 'check_in', $errors);
validateDateRange($checkIn, $checkOut, $errors);

$roomIds = validateIds($_POST['room_ids'] ?? null, 'room_ids', $errors);
$guests  = validateInteger($_POST['guests'] ?? null, 'guests', $errors, 1, 6);

$validated = [
    'check_in' => $checkIn,
    'check_out' => $checkOut,
    'rooms_ids' => $roomIds,
    'guests' => $guests
]; */

header('Location: index.php', true, 303);
exit;
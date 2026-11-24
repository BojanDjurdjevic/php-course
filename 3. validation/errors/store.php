<?php

session_start();

$name = $_POST['name'] ?? null;
$email = $_POST['email'] ?? null;
$age = $_POST['age'] ?? null;

$old = [

    'name' => is_string($name) ? $name : '',
    'email' => is_string($email) ? $email : '',
    'age' => is_string($age) ? $age : '',

];

$errors = [];

if(!is_string($name)) {

    $errors['name'] = 'Name must be a string type.';

} else {

    $name = trim($name);

    if ($name === '') {

        $errors['name'] = 'Name is required!';

    } elseif(mb_strlen($name) > 100) {

        $errors['name'] = 'Name may have up to 100 characters!';

    }
} 

if (!is_string($email)) {

    $errors['email'] = 'Email must be a string!';

} else {

    $email = trim($email);

    if ($email === '') {

        $errors['email'] = 'Email is required!';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors['email'] = 'Email must be valid email address!';

    }
}

 if (!is_string($age)) {

    $errors['age'] = 'Age must be a string!';

 } else {

    $age = trim($age);

    if($age === '') {
        $errors['age'] = 'Age is required!';
    } else {

        $validatedAge = filter_var($age, FILTER_VALIDATE_INT, [

            'options' => [
                'min_range' => 0,
                'max_range' => 100
            ],

        ]);

        if ($validatedAge === false) {
            $errors['age'] = 'Age must be an integer between 0 and 100!';
        }
        else $age = $validatedAge;

    }

 }

if($errors !== []) {
    
    $_SESSION['errors'] = $errors;

    $_SESSION['old'] = $old;
 
} else {
    $_SESSION['success'] = 'The user is stored successfully!';
}

header('Location: index.php', true, 303);
exit;
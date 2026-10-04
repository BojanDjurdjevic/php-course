<?php

declare (strict_types=1);

function validateRequiredString(mixed $value, string $field, array &$errors, int $minLength = 1, int $maxLength = 100): ?string {

    if(!is_string($value)) {

        $errors[$field] = "The $field must be a string!";
        return null;

    } 

    $value = trim($value);

    if($value === '') {

        $errors[$field] = "The field $field is required!";
        return null;

    }

    $length = mb_strlen($value);

    if($length < $minLength) {

        $errors[$field] = "$field must have at least $minLength characters";
        return null;

    }

    if($length > $maxLength) {

        $errors[$field] = "$field may have up to $maxLength characters";
        return null;

    }

    return $value;
}

function validateEmail(mixed $value, string $field, array &$errors): ?string {

    if(!is_string($value)) {
        $errors[$field] = "Field $field must be a string!";
        return null;
    }

    $value = trim($value);

    if($value === '') {
        $errors[$field] = "Field $field is required!";
        return null;
    }

    if(!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        $errors[$field] = "The $field must be valid email address!";
        return null;
    }

    return $value;
}

function validateInteger(mixed $value, string $field, array &$errors, int $min, int $max): ? int {

    if(!is_string($value)) {
        $errors[$field] = "The $field must be a string!";
        return null;
    }

    $value = trim($value);

    if($value === '') {

        $errors[$field] = "The field $field is required!";
        return null;

    }

    $validatedInt = filter_var($value, FILTER_VALIDATE_INT, [
        'options' => [

            'min_range' => $min,

            'max_range' => $max,

        ]
    ]);

    if($validatedInt === false) {

        $errors[$field] = "Field $field must be a number between $min and $max";
        return null;

    }

    return $validatedInt;
}
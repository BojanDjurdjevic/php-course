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

function validateNullableString(mixed $value, string $field, array &$errors, int $max = 30): ?string {

    if($value === null) return null;

    if(!is_string($value)) {
        $errors[$field] = "Field $field must be a string or null!";
        return null;
    }

    $value = trim($value);

    if($value === '') {
        return null;
    }

    if(mb_strlen($value) > $max) {
        $errors[$field] = "Field $field may have up to $max characters";
        return null;
    }

    return $value;
}

function validatePhone( mixed $value, string $field, array &$errors): ?string {

    $value = validateNullableString(
        $value,
        $field,
        $errors,
        30
    );

    if ($value === null) {
        return null;
    }

    $clean = preg_replace('/[\s\-()]/', '', $value);

    if (!preg_match('/^\+?[0-9]{8,15}$/', $clean)) {
        $errors[$field] =
            "Field $field must be a valid phone number.";

        return null;
    }

    return $clean;
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

function validateDate(mixed $value, string $field, array &$errors, string $format = 'Y-m-d'): ? string {
    
    $input = validateRequiredString($value, $field, $errors);

    if($input === null) return null;

    $date = DateTimeImmutable::createFromFormat('!' . $format, $input);

    if($date === false || $date->format($format) !== $input) {
        $errors[$field] = "Field $field must be a valid date in format: $format";

        return null;
    } 

    return $input;

}

function validateNotInPast(?string $date, string $field, array &$errors, string $format = 'Y-m-d'): ?string {

    if ($date === null) return null;

    $d = DateTimeImmutable::createFromFormat('!' . $format, $date);

    if ($d < new DateTimeImmutable('today')) {
        $errors[$field] = "Field $field can't be in the past!";
        return null;
    }

    return $date;
}

function validateDateRange(?string $checkIn, ?string $checkOut, array &$errors, string $format = 'Y-m-d'): bool {

    if ($checkIn === null || $checkOut === null) return false;

    $start = DateTimeImmutable::createFromFormat('!' . $format, $checkIn);
    $end   = DateTimeImmutable::createFromFormat('!' . $format, $checkOut);

    if ($end <= $start) {
        $errors['check_out'] = "Check-out must be after check-in!";
        return false;
    }

    return true;
}

function validateIds(mixed $value, string $field, array &$errors, int $min = 1, int $max = 3): ?array {

    if (!is_array($value)) {
        $errors[$field] = "$field must be an array!";
        return null;
    }

    $count = count($value);

    if ($count < $min) {
        $errors[$field] = "At least $min room must be chosen!";
        return null;
    }

    if ($count > $max) {
        $errors[$field] = "Maximum number of chosen rooms is $max!";
        return null;
    }

    $ids = [];

    foreach ($value as $item) {
        $id = filter_var($item, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);

        if ($id === false) {
            $errors[$field] = "Room id must be a positive integer!";
            return null;
        }

        $ids[] = $id;
    }

    if (count($ids) !== count(array_unique($ids))) {
        $errors[$field] = "There can't be duplicated rooms!";
        return null;
    }

    return $ids;
}
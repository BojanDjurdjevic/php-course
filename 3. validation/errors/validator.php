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

/*
function validateRooms(mixed $value, array &$errors): ? array {

    if(!is_array($value)) {
        $errors['rooms'] = 'Rooms must be an array!';
    }

    $count = count($value);

    if($count < 1) {
        $errors['rooms'] = 'There must be at least 1 room selected!';
    }

    if($count > 3) {
        $errors['rooms'] = 'There can be as maximum 3 room selected!';
    }

    foreach($value as $index => $room) {
        if(!is_array($room)) {
            $errors["rooms.$index"] = 'Room must bee an array!';
            continue;
        }

        $room_id = $room['room_id'] ?? null;
        $adults = $room['adults'] ?? null;
        $children = $room['children'] ?? null;

        if($room_id === null) {
            $errors["rooms.$index.room_id"] = 'Room ID is required!';
            continue;
        } 
        
        $room_id = validateInteger($room_id, "rooms.$index.room_id", $errors, 1, 999999999999999999999999);

        if($adults === null) {
            $errors["rooms.$index.adults"] = 'The number of adult passangers is required!';
            continue;
        } 
        
        $adults = validateInteger($adults, "rooms.$index.adults", $errors, 1, 4);

        if($children === null) {
            $errors["rooms.$index.children"] = 'The number of children is required!';
            continue;
        } 
        
        $children = validateInteger($children, "rooms.$index.children", $errors, 0, 3);




    } 

} 

function validateRooms(mixed $value, array &$errors): ?array {

    if (!is_array($value)) {
        $errors['rooms'] = 'Rooms must be an array!';
        return null;
    }

    $count = count($value);

    if ($count < 1) {
        $errors['rooms'] = 'There must be at least 1 room selected!';
        return null;
    }

    if ($count > 3) {
        $errors['rooms'] = 'There can be a maximum of 3 rooms selected!';
        return null;
    }

    $validated = [];
    $hasErrors = false;
    $seenRoomIds = [];

    foreach ($value as $index => $room) {

        if (!is_array($room)) {
            $errors["rooms.$index"] = 'Room must be an array!';
            $hasErrors = true;
            continue;
        }

        $before = count($errors);

        $roomId   = validateInteger($room['room_id'] ?? '', "rooms.$index.room_id", $errors, 1, PHP_INT_MAX);
        $adults   = validateInteger($room['adults'] ?? '', "rooms.$index.adults", $errors, 1, 4);
        $children = validateInteger($room['children'] ?? '', "rooms.$index.children", $errors, 0, 3);

        if (count($errors) > $before) {
            $hasErrors = true;
            continue;
        }

        if (isset($seenRoomIds[$roomId])) {
            $errors["rooms.$index.room_id"] = 'The same room cannot be selected twice!';
            $hasErrors = true;
            continue;
        }

        $seenRoomIds[$roomId] = true;

        $validated[] = [
            'room_id'  => $roomId,
            'adults'   => $adults,
            'children' => $children,
        ];
    }

    return $hasErrors ? null : $validated;
}
    
*/

function validateRooms(mixed $value, array &$errors): ?array {

    if (!is_array($value)) {
        $errors['rooms'] = 'Rooms must be an array!';
        return null;
    }

    $count = count($value);

    if ($count < 1) {
        $errors['rooms'] = 'There must be at least 1 room selected!';
        return null;
    }

    if ($count > 3) {
        $errors['rooms'] = 'There can be a maximum of 3 rooms selected!';
        return null;
    }

    $validated = [];
    $hasErrors = false;
    $seenRoomIds = [];

    foreach ($value as $index => $room) {

        if (!is_array($room)) {
            $errors["rooms.$index"] = 'Room must be an array!';
            $hasErrors = true;
            continue;
        }

        $roomId = validateInteger( $room['room_id'] ?? '', "rooms.$index.room_id", $errors, 1, PHP_INT_MAX);

        $adults = validateInteger( $room['adults'] ?? '', "rooms.$index.adults", $errors, 1, 4);

        $children = validateInteger( $room['children'] ?? '', "rooms.$index.children", $errors, 0, 3);

        if ($roomId !== null) {
            if (isset($seenRoomIds[$roomId])) {
                $errors["rooms.$index.room_id"] =
                    'The same room cannot be selected twice!';

                $hasErrors = true;
            } else {
                $seenRoomIds[$roomId] = true;
            }
        }

        if ( $roomId === null || $adults === null || $children === null) {
            $hasErrors = true;
            continue;
        }

        $validated[] = [
            'room_id'  => $roomId,
            'adults'   => $adults,
            'children' => $children,
        ];
    }

    return $hasErrors ? null : $validated;
}
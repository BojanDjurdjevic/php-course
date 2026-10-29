<?php 

namespace App\Helpers;

class Validator {

    private array $errors = [];

    public function errors(): array 
    {
        return $this->errors;
    }

    public function resetErrors(): void 
    {
        $this->errors = [];
    } 

    public function validateRequiredString(mixed $value, string $field, int $minLength = 1, int $maxLength = 100): ?string {

        if(!is_string($value)) {

            $this->errors[$field] = "The $field must be a string!";
            return null;

        } 

        $value = trim($value);

        if($value === '') {

            $this->errors[$field] = "The field $field is required!";
            return null;

        }

        $length = mb_strlen($value);

        if($length < $minLength) {

            $this->errors[$field] = "$field must have at least $minLength characters";
            return null;

        }

        if($length > $maxLength) {

            $this->errors[$field] = "$field may have up to $maxLength characters";
            return null;

        }

        return $value;
    }

    public function validateNullableString(mixed $value, string $field, int $max = 30): ?string {

        if($value === null) return null;

        if(!is_string($value)) {
            $this->errors[$field] = "Field $field must be a string or null!";
            return null;
        }

        $value = trim($value);

        if($value === '') {
            return null;
        }

        if(mb_strlen($value) > $max) {
            $this->errors[$field] = "Field $field may have up to $max characters";
            return null;
        }

        return $value;
    }

    public function validateEmail(mixed $value, string $field): ?string {

        if(!is_string($value)) {
            $this->errors[$field] = "Field $field must be a string!";
            return null;
        }

        $value = trim($value);

        if($value === '') {
            $this->errors[$field] = "Field $field is required!";
            return null;
        }

        if(!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "The $field must be valid email address!";
            return null;
        }

        return strtolower($value);
    }

    public function validateInteger(mixed $value, string $field, int $min, int $max): ? int {

        if(!is_string($value)) {
            $this->errors[$field] = "The $field must be a string!";
            return null;
        }

        $value = trim($value);

        if($value === '') {

            $this->errors[$field] = "The field $field is required!";
            return null;

        }

        $validatedInt = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => [

                'min_range' => $min,

                'max_range' => $max,

            ]
        ]);

        if($validatedInt === false) {

            $this->errors[$field] = "Field $field must be a number between $min and $max";
            return null;

        }

        return $validatedInt;
    }

    public function validatePassword(mixed $value, string $field, int $min = 8, int $max = 30): ?string 
    {
        if(!is_string($value)) {

            $this->errors[$field] = "The $field must be a string!";
            return null;

        } 

        $len = mb_strlen($value);

        if($len < $min || $len > $max) {
            $this->errors[$field] = "$field must contain between $min and $max characters";

            return null;
        } 

        return $value;
    } 

    public function validatePasswordConfirm(mixed $value, string $field, mixed $confirm): bool 
    {
        if($value !== $confirm) {
            $this->errors[$field] = "Password confirmation $confirm must match the password";
            return false;
        }

        return true;
    } 

    /*
    public function validatePhone( mixed $value, string $field, array &$errors): ?string {

        $value = validateNullableString(
            $value,
            $field,
            $this->errors,
            30
        );

        if ($value === null) {
            return null;
        }

        $clean = preg_replace('/[\s\-()]/', '', $value);

        if (!preg_match('/^\+?[0-9]{8,15}$/', $clean)) {
            $this->errors[$field] =
                "Field $field must be a valid phone number.";

            return null;
        }

        return $clean;
    } */

}
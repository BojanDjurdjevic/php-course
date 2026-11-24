<?php

// round() - ceil() 

echo "<b>" . "round() - ceil() - floor()" . "</b>" . "</br>";

$num = 3.58;

echo "Round: " . round($num) . "</br>" . "</br>"; 

echo "Ceil: " . ceil($num) . "</br>" . "</br>";

echo "Floor: " . floor($num) . "</br>" . "</br>";

echo "</br>" . "</br>";

//------------------------------------------------------

// abs() // apsolutna vrednost

echo "<b>" . "abs() " . "</b>" . "</br>";

echo abs(-15) . "</br>";

$expected = 100;
$actual = 93;

$difference = abs($expected - $actual);

echo $difference;
// 7

echo "</br>" . "</br>";

//------------------------------------------------------

// min() - max() 

echo "<b>" . "min() - max() " . "</b>" . "</br>";

$prices = [120, 89, 240, 175];

echo min($prices) . "</br>";
// 89

echo max($prices);
// 240

echo "</br>" . "</br>";

/**
 * LARAVEL
  * $prices = collect([120, 89, 240, 175]);

  *  $prices->min();
  *  $prices->max();
 */

//------------------------------------------------------

// PHP nema klasičnu sum() za brojeve kao Laravel, ima samo array_sum()

//------------------------------------------------------

// intdiv() // celobrojno deljenje float-a

echo "<b>" . "intdiv() " . "</b>" . "</br>";

$minutes = 135;

$hours = intdiv($minutes, 60);
$remaining = $minutes % 60;

echo "{$hours}h {$remaining}min";
// 2h 15min

echo "</br>" . "</br>";

//------------------------------------------------------

// fmod() // float modulo tj % za float broj

echo "<b>" . "fmod() " . "</b>" . "</br>";

$result = fmod(5.7, 1.3);

echo "</br>" . "</br>";

//------------------------------------------------------

// is_numeric() // proverava da li je nešto broj ili se može konvertovati u br

echo "<b>" . "is_numeric() " . "</b>" . "</br>";

var_dump(is_numeric(123));
// true

var_dump(is_numeric('123'));
// true

var_dump(is_numeric('12.50'));
// true

var_dump(is_numeric('hello'));
// false

echo "</br>" . "</br>";

//------------------------------------------------------

// is_int() - is_float // 

echo "<b>" . "is_int() - is_float " . "</b>" . "</br>";

$number = 10;

is_int($number);
// true

$number = 10.5;

is_float($number);
// true

is_int('10');
// false

// Laravel:
/*
$request->validate([
    'quantity' => ['required', 'integer', 'min:1'],
    'price' => ['required', 'numeric', 'min:0'],
]); */

echo "</br>" . "</br>";

//------------------------------------------------------

// random_int() // nasumičan broj

echo "<b>" . "random_int() " . "</b>" . "</br>";

$number = random_int(1, 100);

echo "</br>" . "</br>";

//------------------------------------------------------

// number_format() // formatira br --- Vraća STRING 

echo "<b>" . "number_format() " . "</b>" . "</br>";

$price = 1234567.5;

echo number_format($price, 2) . "</br>";

echo number_format($price, 2, ',', '.'); // sa separatorima

/**
 * LARAVEL
 * 
 * Number::format()
 * Number::currency()
 * Number::percentage() / clamp() / abbreviate() / forHumans() / fileSize() / spell() etc.
 */

echo "</br>" . "</br>";

//------------------------------------------------------

?>
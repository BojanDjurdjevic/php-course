<?php

/**
 * 
 *   add age,

 *   keep only people under 50,

 *   extract only their names.
 */

$people = [
    [
        'name' => 'Perica',
        'birth_year' => 1977,
        'city' => 'Novi Sad',
    ],
    [
        'name' => 'Mita',
        'birth_year' => 1989,
        'city' => 'Beograd',
    ],
    [
        'name' => 'Ivica',
        'birth_year' => 1941,
        'city' => 'Novi Sad',
    ],
    [
        'name' => 'Ana',
        'birth_year' => 1995,
        'city' => 'Niš',
    ],
];

$withAge = array_map(function ($person) {
    $person['age'] = date('Y') - $person['birth_year'];

    return $person;
}, $people);

$filtered = array_filter(
    $withAge,
    fn ($person) => $person['age'] < 50
);

$names = array_column($filtered, 'name');

print_r($names);

//  array_column() ovde već pravi novi numerički indeksiran niz, pa array_values() nije potreban.

//-----------------------------------------------------------------------------------------------

$input = '   PHP, Laravel, vue, MYSQL, php, laravel   ';

/**
 * $input = '   PHP, Laravel, vue, MYSQL, php, laravel   ';

  *  Treba dobiti:

  *  [
  *      'php',
  *      'laravel',
  *      'vue',
  *      'mysql',
  *  ]
 */

$trimmed = trim($input);

$arr = explode(',', $trimmed);

$normalized = array_map(
    fn ($item) => mb_strtolower(trim($item)),
    $arr
);

$unique = array_unique($normalized);

$final = array_values($unique);

print_r($final);

// -------------------------------------------------------------------------------------------------

$pricePerNight = 87.50;
$checkIn = '2026-10-12';
$checkOut = '2026-10-17';
$discount = 15;

/**
 * Calculate:

 *   number of nights,

 *   subtotal,

 *   discount,

 *   total.
 */

$start = new DateTime($checkIn);
$end = new DateTime($checkOut);

$nights = $start->diff($end)->days;

$priceInCents = (int) round($pricePerNight * 100);

$subtotalInCents = $priceInCents * $nights;

$discountInCents = (int) round(
    $subtotalInCents * $discount / 100
);

$totalInCents = $subtotalInCents - $discountInCents;

echo "Nights: {$nights}" . PHP_EOL;

echo 'Subtotal: '
    . number_format($subtotalInCents / 100, 2)
    . ' EUR'
    . PHP_EOL;

echo 'Discount: '
    . number_format($discountInCents / 100, 2)
    . ' EUR'
    . PHP_EOL;

echo 'Total: '
    . number_format($totalInCents / 100, 2)
    . ' EUR'
    . PHP_EOL;

// ----------------------------------------------------------------------------------

$prices = [
    '129.99',
    85,
    '199.50',
    74.25,
    'invalid',
    150,
];

/**
 * remove invalid values,

 *   convert valid to float,

 *   find the minimum,

 *   maximum,

 *   sum,

 *   average,

 *   format to two decimal places.
 */

$valid = array_filter(
    $prices,
    is_numeric(...)
);

$floats = array_map(
    fn ($price) => (float) $price,
    $valid
);

$count = count($floats);

$minPrice = $count > 0
    ? number_format(min($floats), 2)
    : number_format(0, 2);

$maxPrice = $count > 0
    ? number_format(max($floats), 2)
    : number_format(0, 2);

$sum = array_sum($floats);

$formattedSum = number_format($sum, 2);

$average = $count > 0
    ? number_format($sum / $count, 2)
    : number_format(0, 2);

echo "Min: {$minPrice}" . PHP_EOL;
echo "Max: {$maxPrice}" . PHP_EOL;
echo "Sum: {$formattedSum}" . PHP_EOL;
echo "Average: {$average}" . PHP_EOL;


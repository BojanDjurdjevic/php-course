<?php

// change_key-case - Promena stringova unutar niza u MALA/VELIKA slova

$arr = [
    "Bojan" => 40,
    "Petar" => 20,
    "Maja" => 25,
    "Valentina" => 34,
    "Miroslav" => 45,
    "Nenad" => 18,
    "Stanislava" => 62,
    "Mirka" => 32
];

print_r(array_change_key_case($arr, CASE_UPPER));
echo "<br>";
echo "<br>";

// array_chunk(array, size, preserve_key), array & size: required - Cepa array na komade, size arg odlucuje broj delova. 
//preserve_key: false je defaultni, true -> zadrzava kljuceve

print_r(array_chunk($arr, 3, true));
echo "<br>";
echo "<br>";

/**
    // array_column(array, column_name, index_key) 
    array - required
    column_key - required. Nazivi ključeva (kolone) koje treba vratitu iz niza
    index_key - optional. Koju cemo lolonu koristiti as index vracenog niza
 */

$a = [
    [
        'id' => 1,
        'ime' => 'Perica',
        'prezime' => 'Ognjenović',
        'godiste' => 1977,
    ],
    [
        'id' => 2,
        'ime' => 'Mita',
        'prezime' => 'Ružić',
        'godiste' => 1989,
    ],
    [
        'id' => 3,
        'ime' => 'Ivica',
        'prezime' => 'Osim',
        'godiste' => 1941,
    ]
];

$last_names = array_column($a, 'prezime', 'id');

print_r($last_names);
echo "<br>";
echo "<br>";

//print_r($last_names[3]);

// VEŽBA: Ispiši samo godine od svake osobe (br godina, ne godište)

/* Rešenje 1: Lak i brz ispis - bolje kada nije kompleksno. 
foreach($a as $person) {
    $name = $person['ime'];
    //$currentYear = date('Y');
    //$yearOfBirth = $person['godiste'];
    $years = date('Y') - $person['godiste'];
    //print_r((int)date('Y') - $person["godiste"]);
    echo "Osoba {$name} ima {$years} godina.";
    echo "<br>";
    echo "<br>";
} */

// Rešenje 2: Elegantnije i brže, poželjno kada je potrebna transformacija.
$people = array_map(function($person) {
    $person['godine'] = date('Y') - $person['godiste'];

    return $person;
}, $a);

print_r($people);


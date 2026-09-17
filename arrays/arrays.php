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

// ---------------------------------------

/**
    // array_column(array, column_name, index_key) 
    array - required
    column_key - required. Nazivi ključeva (kolone) koje treba vratitu iz niza
    index_key - optional. Koju cemo lolonu koristiti as index vracenog niza
    Laravel -> pluck()
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

// ---------------------------------------

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
echo "<br>";
echo "<br>";

// ---------------------------------------

// array_combine(keys, values)

/**
 *  Oba arg obavezna, pravi se kombinovani niz od ova dva. OBA niza MORAJU imati isti br elemenata!!!
 */

$fnames = ['Petar', 'Lazar', 'Života'];
$ages = ['35', '39', '54'];

$combine = array_combine($fnames, $ages);
print_r($combine);

echo "<br>";
echo "<br>";

//----------------------------------------

// array_count_values(array) ->  Vraća ukupan broj istih-identičnih vrednosti niza -> Laravel: countBy() // npr: Koliko puta se ponavlja ime Petar u nizu, tako za svakog člana niza

$all = ['A', 'Cat', 'Dog', 'A', 'Car', 'Dog', 'Dog', 'Car'];
print_r(array_count_values($all));
echo "<br>";
echo "<br>";

// ---------------------------------------

// array_diff() -> Razlika između nizova -> Laravel: diff() 

/**
 *  array_diff(arr1, arr2, arr3?)
 * 
 * 1 Required. Niz iz kojeg upoređujemo
 * 2 Required. Niz sa kojim upoređujemo prvi
 * 3... optional. više nizova sa kojim upoređujemo prvi
 * 
 * 
 *   Returns Value:	Returns an array containing the entries from array 1 that are not present in any of the other arrays
 */

$a1 = [1 => 'Pera', 2 => 'Mika', 3 => 'Žika', 4 => 'Bojan', 5 => 'Laza', 6 => 'Mira'];
$a2 = [1 => 'Pera', 2 => 'Mika', 3 => 'Rale', 4 => 'Sale', 5 => 'Laza', 6 => 'Mira'];
$a3 = [1 => 'Pera', 2 => 'Mika', 3 => 'Žika', 4 => 'Rale', 5 => 'Kosta', 6 => 'Marko'];

$result = array_diff($a1, $a2, $a3);
print_r($result);
echo "<br>";
echo "<br>";

//-------------------------------

// array_flip() -> zamenjuje ključeve i vrednosti -> Laravel: flip()

$a4 = ['a' => 'crvena', 'b' => 'plava', 'c' => 'žuta'];
$result = array_flip($a4);

print_r($result);
echo "<br>";
echo "<br>";

// ---------------------------------------

// array_intersect(array1, array2, ...) -> Suprotno od diff() / vraća članove 1 niza koji su prisutni u ostalim -> Laravel: intersect()

$a1 = ["a"=>"red","b"=>"green","c"=>"blue","d"=>"yellow"];
$a2 = ["e"=>"red","f"=>"black","g"=>"purple"];
$a3 = ["a"=>"red","b"=>"black","h"=>"yellow"];

$result = array_intersect($a1,$a2,$a3);
print_r($result);

// ---------------------------------------

//array_key_exists(key, array) -> Proverava da li zadati ključ postoji u nizu -> Laravel: has() "približno"

$a5 = ['Alfa Romeo' => 'Giulia', 'Audi' => 'A6', 'Changan' => 'S35+'];

$output = array_key_exists('Alfa Romeo', $a5) ? "Key exists - Model is {$a5['Alfa Romeo']}" : "Key doesn't exist";
echo $output;
echo "<br>";
echo "<br>";

// ---------------------------------------

//array_keys(array, value, strict) VS array_values() -> Vraća samo ključeve odnosno samo vrednosti niza -> Laravel: keys() / values()

/**
 * array - required. Ostali opciono.
 * value - ako se navede, samo ključevi s tom vrednošću će biti vraćeni.
 * strict - po default-u je false.
 */

$a6 = ["Volvo"=>"XC90", "BMW"=>"X5", "Toyota"=>"Highlander"];
print_r(array_keys($a6 /*, "Highlander" */)); // -> Volvo, BMW, Toyota / odkomentarisano: samo Toyota
echo "<br>";
echo "<br>";

//values()

$a7 = ["Name"=>"Peter","Age"=>"41","Country"=>"USA"];
print_r(array_values($a7));
echo "<br>";
echo "<br>";

// -------------------------------------

// array_merge() -> Spaja (konkatenacija) nizove -> Laravel: merge()

$a1 = ["red","green"];
$a2 = ["blue","yellow"];
print_r(array_merge($a1,$a2));
echo "<br>";
echo "<br>";

// -------------------------------------

//array_pop(array) -> deletes the last one -> Laravel: pop() / Ako je niz prazan, ili podatk nije niz, vratiće NULL 

$b = ['Srbija', 'Hrvatska', 'Bosna i Hercegovina'];
$result = array_pop($b);
print_r($result);
print_r($b);
echo "<br>";
echo "<br>";

//array_shift -> skida prvi u nizu -> Laravel: shift()

$a = ["a" => "red","b" => "green","c" => "blue"];
echo array_shift($a);
print_r ($a);
echo "<br>";
echo "<br>";

//array_push(array) -> dodaje na kraj -> Laravel: shift
//array_unshift() -> dodaje na početak niza -> Laravel: prepend()

// -------------------------------------

//array_reverse() -> obrće redosled -> Laravel: reverse()


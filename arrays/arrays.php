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

//array_reverse(array, preserve - optional (bool), da li se čuvaju ili ne ključevi) -> obrće redosled -> Laravel: reverse()

$cars = ['Alfa Romeo' => 'Giulietta', 'Fiat' => '500L', 'Lancia' => 'Delta', 'Opel' => 'Astra K'];

$reversecars = array_reverse($cars);
$preserve = array_reverse($cars, true);

print_r($reversecars);
echo "</br>";
print_r($preserve);
echo "<br>";
echo "<br>";

// -------------------------------------

// array_search(value, array, strict - optional (bool), ako hoćemo da se razlikuju po tipu) -> pronalazi i vraća ključ zadate vrednosti -> Laravel: search()

/**
 * Ako nađe vrednost -> vraća njen ključ (prvi koji se poklapa)
 * Ako ne nađe -> FALSE
 * Ako je pogrešan parametar -> NULL
 */

$fruits = ['a' => 'Kruška', 'b' => 'Jabuka', 'c' => 9, 'd' => 'Grožđe', 'e' => "9", 'f' => 'Šljiva', 'g' => "9"];

echo array_search('Jabuka', $fruits);
echo "</br>";
echo array_search('9', $fruits, true);

echo "<br>";
echo "<br>";

// -------------------------------------

// array_slice(array, start, length - optional (num), preserve (bool)) -> iseca određenji deo niza od čega nastaje novi niz -> Laravel: slice()

/**
 * start - Određuje odakle će početi sečenje, tj od kog indexa (počinje se od narednog / 2 -> od 3). 
 * Ako je negativan br, broji se od poslednjeg ind i sečenje počinje u zavisnosti od broja (-2 -> pretposlednji ind).
 * 
 * preserve - ako je na true -> zadržava svoje ključeve (index ako nije assoc) iz prethodnog niza. 
 */

$colors = ["red","green","blue","yellow","brown","white"];

print_r(array_slice($colors, 2));
echo "</br>";

print_r(array_slice($colors, -3, 2));
echo "</br>";

print_r(array_slice($colors, 2, 3, true));
echo "<br>";
echo "<br>";

// -------------------------------------

// array_sum(array) -> Vraća zbir elemenata niza -> Laravel: sum()

$a= ["a" => 52.2,"b" => 13.7,"c" => 0.9];
echo array_sum($a);

echo "<br>";
echo "<br>";

// -------------------------------------

// array_unique(array, sorttype - optionnal (određuje kako se vrši poređenje)) -> Uklanja duplikate -> Laravel: unique()

$colors = ["red", "green", "red", "blue", "yellow", "brown", "white", "red"];

print_r(array_unique($colors));

echo "<br>";
echo "<br>";

// -------------------------------------

// in_array(value, array, strict - optional (bool)) -> Proverava da li je zdata vrednost u nizu -> Laravel: contains()

/**
 * strict - Ako je true, biće proveravani i tipovi podataka, a ako je value string, biće i case-sensitive.
 */

$names = ["Peter", "Joe", "Glenn", "Cleveland", 23];

if(in_array("23", $names, true)) {
    echo "23 je u nizu";
} else {
    echo "23 nije u nizu";
}

echo "</br>";

if(in_array(23, $names, true)) {
    echo "23 je u nizu";
} else {
    echo "23 nije u nizu";
}

echo "</br>";

if(in_array("Glenn", $names, true)) {
    echo "Glenn je u nizu";
} else {
    echo "Glenn nije u nizu";
}

echo "<br>";
echo "<br>";

// -------------------------------------

// sort(array, sorttypes) - asc / rsort() - desc ->  sortira običan niz rastuće / opadajuće (ASC / DESC) -> Laravel: sort()

$numbers = [4, 6, 2, 22, 11];
sort($numbers); 
print_r($numbers);
echo "</br>";

$cars = ["Volvo","BMW", "Alfa Romeo", "Toyota"];
rsort($cars);
print_r($cars);

echo "<br>";
echo "<br>";

// -------------------------------------
// asort() / arsort() -> sortira assoc niz ASC / DESC prema vrednostima (čuva ključeve) -> Laravel: sort()

$age = ["Peter" => "35", "Ben" => "55", "Joe" => "43"];
asort($age);
print_r($age);
echo "</br>";

arsort($age);
print_r($age);

echo "<br>";
echo "<br>";

// -------------------------------------
// ksort(array) / krsort(array) -> sortira ASC DESC po ključevima -> Laravel: sortKeys()

ksort($age);
print_r($age);
echo "</br>";

krsort($age);
print_r($age);

echo "<br>";
echo "<br>";

// -------------------------------------
// usort(array, callback) / custom sortiranje -> Laravel: callbackomsort() / sortBy()

function my_sort($a, $b) {
  if ($a == $b) return 0;
  return ($a < $b) ? -1 : 1;
}

$a = array(4, 2, 8, 6);
usort($a, "my_sort");

foreach($a as $key => $value) {
  echo "[" . $key . "] => " . $value;
  echo "<br>";
}

echo "<br>";
echo "<br>";

// ---------------------------- VAŽNO - čuvena trojka kao u JS -------------------------------------------//

// array_map(my_function, array1, array2, ...) -> transformiše svaki element prema uputstvima -> Laravel: map()

/**
 * Šalje svaki element niza u custom made funkciju koja proverava svaki element i transformiše ga po potrebi.
 * Vraća novi niz sa transformisanim vrednostima.
 */

$osobe = [
    [
        'ime' => 'Bojan',
        'prezime' => 'Đurđević',
        'godine' => 40
    ],

    [
        'ime' => 'Milan',
        'prezime' => 'Vasić',
        'godine' => 45
    ],

    [
        'ime' => 'Jovan',
        'prezime' => 'Jović',
        'godine' => 17
    ],
];

$osobe2 = array_map(function ($osoba) {
    $osoba['punoletan'] = $osoba['godine'] > 17 ? true : false;

    return $osoba;
}, $osobe);

print_r($osobe2);

echo "<br>";
echo "<br>";

// -------------------------------------

// array_filter(array, callback, flag) -> Filtrira vrednosti niza, prema callbackfunkciji.
/**
 * Klučevi ostaju očuvani.
 * Filtriraju se vrednosti prema callback-u, i ako vrednost odgovara filteru (ako je true), vrednost ostaje u nizu.
 */

$osobe3 = array_filter($osobe2, fn($osoba) => $osoba['punoletan']);
print_r($osobe3);

echo "<br>";
echo "<br>";

// -------------------------------------

// array_reduce(array, my_function, initial)
/**
*  MAP       jedan element → transformacija u drugi element
*  FILTER    mnogo elemenata → ostaju samo oni koji zadovoljavaju uslov
*  REDUCE    mnogo elemenata → jedna vrednost na kraju
 */

$brojevi = [5, 10, 15, 25, 30];
$animals = ["Dog", "Cat", "Fox", "Horse", "Wolf"];

$novi_br = array_reduce($brojevi, fn($carry, $item) => $carry + $item);
$new_animals = array_reduce($animals, fn($carry, $item) => $carry . " - " . $item);

print_r($novi_br);
echo "</br>";
print_r($new_animals);
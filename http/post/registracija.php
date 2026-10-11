<?php

$ime = $_POST['ime'] ?? null;
$lozinka = $_POST['lozinka'] ?? null;

if($ime === null) die('Morate poslati ime.');

if($lozinka === null) die('Morate poslati lozinku.');

if(!is_string($ime)) die('Ime mora biti string.');

if(!is_string($lozinka)) die('Lozinka mora biti string');

$ime = trim($ime);
$lozinka = trim($lozinka);

if($ime === '') die('Ime je obavezno polje');

if($lozinka === '') die('Lozinka je obavezno polje');

$duzinaImena = mb_strlen($ime);
$duzinaLozinke = mb_strlen($lozinka);

if($duzinaImena < 2) die('Ime mora imati najmanje 2 karaktera.');

if($duzinaImena > 55) die('Ime može imati do 55 karaktera');

if($duzinaLozinke < 8 || $duzinaLozinke > 30) die('Lozinka može imati od 8 do 30 karaktera.');

echo "Ime: $ime"  . "</br>" . "Lozinka: $lozinka"; 

?>
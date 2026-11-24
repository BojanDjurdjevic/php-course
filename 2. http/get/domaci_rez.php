<?php

function izracunajCenu(int $cena, string $proizvod, bool $porez) {
    $ukupno = 0;

    $dodatakHrana = 50;
    $dodatakOprema = 350;

    if($proizvod === 'hrana') {
        $ukupno = $cena + $dodatakHrana;
    } elseif($proizvod === 'oprema') {
        $ukupno = $cena + $dodatakOprema;
    } 

    $ukupno = $porez ? $ukupno * 1.20 :  $ukupno;

    return $ukupno;
}

$cena = isset($_GET['cena']) ? $_GET['cena'] : null;

$proizvod = isset($_GET['proizvod']) ? $_GET['proizvod'] : null;

$saPorezom = isset($_GET['porez']) ? true : false;


if(!$cena || !$proizvod) {
    echo "Morate uneti cenu i proizvod!";
    exit();
}

$ukupno = izracunajCenu($cena, $proizvod, $saPorezom);

echo $ukupno . " din";
?>
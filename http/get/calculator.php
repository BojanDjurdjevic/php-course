<?php

// calculator.php?broj_1=12&broj_2=55

$broj_1 = isset($_GET['broj_1']) ? (int)$_GET['broj_1'] : 0;
$broj_2 = isset($_GET['broj_2']) ? (int)$_GET['broj_2'] : 0;

$operacija = isset($_GET['operacija']) ? $_GET['operacija'] : 'sabiranje';

$rezultat = $operacija == 'sabiranje' ? $broj_1 + $broj_2 : $broj_1 - $broj_2;


echo $rezultat;
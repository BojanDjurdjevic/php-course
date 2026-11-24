<?php
// --------------------- DOMAĆI 1 ---------------------------
$ime = 'administrator';
$lozinka = "mojaSifraJeSigurna";

$imeMalim = strtolower($ime);

if ($imeMalim == 'administrator' && $lozinka == 'mojaSifraJeSigurna') echo "Dobrodošao admine!";
else echo "Nevalidni kredencijali.";

echo "</br></br>";

// --------------------- DOMAĆI 2 ---------------------------
date_default_timezone_set("Europe/Belgrade");
$now = date('H');

if ($now >= 5 && $now < 12) 
{
    echo "Dobro jutro!";
} elseif ( $now >= 12 && $now < 20 )
{
    echo "Dobar dan!";
} else 
{
    echo "Dobro veče!";
}

?>
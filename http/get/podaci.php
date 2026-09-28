<?php

//podaci.php?provera_sigurnosti=on

$provera = isset($_GET['provera_sigurnosti']) ? 'tačno' : 'netačno';

echo $provera;

?>
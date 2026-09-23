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

// abs() // apsolutna vrednost

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

?>